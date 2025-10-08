<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Security\AdminSectionVoter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted(AdminSectionVoter::VIEW_ORDERS)]
class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Order')
            ->setEntityLabelInPlural('Orders')
            ->setSearchFields(['orderNumber', 'customer.firstName', 'customer.lastName', 'customer.email', 'customer.phone', 'shippingAddress.city', 'shippingAddress.state'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel('View');
            })
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);

        // Only allow management actions if user has MANAGE_ORDERS permission
        if ($this->isGranted(AdminSectionVoter::MANAGE_ORDERS)) {
            // Custom action to mark as shipped
            $markShipped = Action::new('markShipped', 'Mark as Shipped', 'fa fa-shipping-fast')
                ->linkToCrudAction('markAsShipped')
                ->displayIf(function ($entity) {
                    return $entity->getStatus() === Order::STATUS_PROCESSING;
                })
                ->setCssClass('btn btn-sm btn-primary');

            // Custom action to mark as delivered
            $markDelivered = Action::new('markDelivered', 'Mark as Delivered', 'fa fa-check-circle')
                ->linkToCrudAction('markAsDelivered')
                ->displayIf(function ($entity) {
                    return $entity->getStatus() === Order::STATUS_SHIPPED;
                })
                ->setCssClass('btn btn-sm btn-success');

            $actions
                ->add(Crud::PAGE_INDEX, $markShipped)
                ->add(Crud::PAGE_INDEX, $markDelivered)
                ->add(Crud::PAGE_DETAIL, $markShipped)
                ->add(Crud::PAGE_DETAIL, $markDelivered)
                ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                    return $action->setIcon('fa fa-edit')->setLabel('Edit Status');
                });
        } else {
            // Remove management actions for users without MANAGE_ORDERS
            $actions
                ->remove(Crud::PAGE_INDEX, Action::EDIT)
                ->remove(Crud::PAGE_DETAIL, Action::EDIT);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        // Common fields for all pages
        $id = IdField::new('id')->hideOnForm();
        $orderNumber = TextField::new('orderNumber')
            ->setLabel('Order Number')
            ->hideOnForm();
        
        $customer = AssociationField::new('customer')
            ->setLabel('Customer')
            ->formatValue(function ($value, $entity) {
                if ($value) {
                    return sprintf('%s %s', $value->getFirstName(), $value->getLastName());
                }
                return '';
            });

        $customerDetail = AssociationField::new('customer')
            ->setLabel('Customer Information')
            ->formatValue(function ($value, $entity) {
                if ($value) {
                    return sprintf('%s %s<br><small class="text-muted">%s</small><br><small class="text-muted">%s</small>', 
                        $value->getFirstName(),
                        $value->getLastName(),
                        $value->getEmail(),
                        $value->getPhone() ?: 'No phone'
                    );
                }
                return '';
            });

        $totalAmount = MoneyField::new('totalAmount')
            ->setCurrency('USD')
            ->setLabel('Total Amount')
            ->setNumDecimals(2)
            ->hideOnForm();

        $status = ChoiceField::new('status')
            ->setChoices([
                'Pending' => Order::STATUS_PENDING,
                'Processing' => Order::STATUS_PROCESSING,
                'Shipped' => Order::STATUS_SHIPPED,
                'Delivered' => Order::STATUS_DELIVERED,
                'Cancelled' => Order::STATUS_CANCELLED,
            ])
            ->setRequired(true)
            ->formatValue(function ($value, $entity) {
                $badges = [
                    Order::STATUS_PENDING => '<span class="badge badge-secondary">Pending</span>',
                    Order::STATUS_PROCESSING => '<span class="badge badge-info">Processing</span>',
                    Order::STATUS_SHIPPED => '<span class="badge badge-primary">Shipped</span>',
                    Order::STATUS_DELIVERED => '<span class="badge badge-success">Delivered</span>',
                    Order::STATUS_CANCELLED => '<span class="badge badge-danger">Cancelled</span>',
                ];
                return $badges[$value] ?? $value;
            });

        $createdAt = DateTimeField::new('createdAt')
            ->setLabel('Order Date')
            ->hideOnForm()
            ->setFormat('medium');

        // Additional fields for detail and form pages
        $subtotal = MoneyField::new('subtotal')
            ->setCurrency('USD')
            ->setLabel('Subtotal')
            ->setNumDecimals(2)
            ->hideOnForm();

        $taxAmount = MoneyField::new('taxAmount')
            ->setCurrency('USD')
            ->setLabel('Tax Amount')
            ->setNumDecimals(2);

        $shippingAmount = MoneyField::new('shippingAmount')
            ->setCurrency('USD')
            ->setLabel('Shipping Amount')
            ->setNumDecimals(2);

        $shippingAddress = AssociationField::new('shippingAddress')
            ->setLabel('Shipping Address')
            ->formatValue(function ($value, $entity) {
                if ($value) {
                    return sprintf('%s, %s, %s %s, %s', 
                        $value->getStreet(), 
                        $value->getCity(), 
                        $value->getState(), 
                        $value->getPostalCode(),
                        $value->getCountry()
                    );
                }
                return '';
            });

        $orderItems = CollectionField::new('orderItems')
            ->setLabel('Order Items')
            ->hideOnForm()
            ->setTemplatePath('admin/field/order_items.html.twig')
            ->formatValue(function ($value, $entity) {
                if ($value && count($value) > 0) {
                    $items = [];
                    foreach ($value as $item) {
                        $product = $item->getProduct();
                        $items[] = [
                            'product_name' => $product->getName(),
                            'product_sku' => $product->getSku(),
                            'quantity' => $item->getQuantity(),
                            'unit_price' => $item->getUnitPrice(),
                            'line_total' => $item->getLineTotal(),
                            'supplier' => $product->getSupplier() ? $product->getSupplier()->getCompanyName() : 'N/A'
                        ];
                    }
                    return $items;
                }
                return [];
            });

        // Virtual fields using TextField to avoid property mapping issues
        $itemCount = TextField::new('itemCount')
            ->setLabel('Items Count')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                return $entity->getItemCount();
            });

        $totalQuantity = TextField::new('totalQuantity')
            ->setLabel('Total Quantity')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                return $entity->getTotalQuantity();
            });

        $shippedAt = DateTimeField::new('shippedAt')
            ->setLabel('Shipped At')
            ->hideOnForm()
            ->setFormat('medium');

        $deliveredAt = DateTimeField::new('deliveredAt')
            ->setLabel('Delivered At')
            ->hideOnForm()
            ->setFormat('medium');

        $updatedAt = DateTimeField::new('updatedAt')
            ->setLabel('Last Updated')
            ->hideOnForm()
            ->setFormat('medium');

        // Configure fields based on page
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $orderNumber,
                $customer,
                $totalAmount,
                $status,
                $itemCount,
                $createdAt,
            ];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $orderNumber,
                $customerDetail,
                $status,
                $subtotal,
                $taxAmount,
                $shippingAmount,
                $totalAmount,
                $shippingAddress,
                $orderItems,
                $itemCount,
                $totalQuantity,
                $createdAt,
                $shippedAt,
                $deliveredAt,
                $updatedAt,
            ];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [
                $status,
                $taxAmount,
                $shippingAmount,
            ];
        }

        return [];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Pending' => Order::STATUS_PENDING,
                'Processing' => Order::STATUS_PROCESSING,
                'Shipped' => Order::STATUS_SHIPPED,
                'Delivered' => Order::STATUS_DELIVERED,
                'Cancelled' => Order::STATUS_CANCELLED,
            ])->setLabel('Status'))
            ->add(EntityFilter::new('customer')->setLabel('Customer'))
            ->add(DateTimeFilter::new('createdAt')->setLabel('Order Date'))
            ->add(DateTimeFilter::new('shippedAt')->setLabel('Shipped Date'))
            ->add(DateTimeFilter::new('deliveredAt')->setLabel('Delivered Date'))
            ->add(NumericFilter::new('totalAmount')->setLabel('Total Amount'));
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Ensure timestamps are updated when status changes
        if ($entityInstance instanceof Order) {
            $entityInstance->setUpdatedAtValue();
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }

    #[IsGranted(AdminSectionVoter::MANAGE_ORDERS)]
    public function markAsShipped(EntityManagerInterface $entityManager): Response
    {
        $order = $this->getContext()->getEntity()->getInstance();
        
        if ($order instanceof Order && $order->getStatus() === Order::STATUS_PROCESSING) {
            $order->setStatus(Order::STATUS_SHIPPED);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf('Order %s has been marked as shipped.', $order->getOrderNumber()));
        } else {
            $this->addFlash('error', 'Order cannot be marked as shipped.');
        }
        
        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }

    #[IsGranted(AdminSectionVoter::MANAGE_ORDERS)]
    public function markAsDelivered(EntityManagerInterface $entityManager): Response
    {
        $order = $this->getContext()->getEntity()->getInstance();
        
        if ($order instanceof Order && $order->getStatus() === Order::STATUS_SHIPPED) {
            $order->setStatus(Order::STATUS_DELIVERED);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf('Order %s has been marked as delivered.', $order->getOrderNumber()));
        } else {
            $this->addFlash('error', 'Order cannot be marked as delivered.');
        }
        
        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }
}