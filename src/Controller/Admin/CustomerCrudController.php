<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Security\AdminSectionVoter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;

#[IsGranted(AdminSectionVoter::VIEW_CUSTOMERS)]
class CustomerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Customer')
            ->setEntityLabelInPlural('Customers')
            ->setSearchFields(['firstName', 'lastName', 'email', 'phone'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye');
            });

        // Only allow management actions if user has MANAGE_CUSTOMERS permission
        if ($this->isGranted(AdminSectionVoter::MANAGE_CUSTOMERS)) {
            // Custom action for quick status toggle
            $toggleStatus = Action::new('toggleStatus', 'Toggle Status', 'fa fa-toggle-on')
                ->linkToCrudAction('toggleCustomerStatus')
                ->displayIf(function ($entity) {
                    return $entity instanceof Customer;
                });

            $actions
                ->add(Crud::PAGE_INDEX, $toggleStatus)
                ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                    return $action->setIcon('fa fa-plus')->setLabel('Add Customer');
                })
                ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                    return $action->setIcon('fa fa-edit');
                })
                ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                    return $action->setIcon('fa fa-trash');
                });
        } else {
            // Remove management actions for users without MANAGE_CUSTOMERS
            $actions
                ->remove(Crud::PAGE_INDEX, Action::NEW)
                ->remove(Crud::PAGE_INDEX, Action::EDIT)
                ->remove(Crud::PAGE_INDEX, Action::DELETE)
                ->remove(Crud::PAGE_DETAIL, Action::EDIT)
                ->remove(Crud::PAGE_DETAIL, Action::DELETE);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        // Common fields for all pages
        $id = IdField::new('id')->hideOnForm();
        $firstName = TextField::new('firstName')->setRequired(true);
        $lastName = TextField::new('lastName')->setRequired(true);
        $email = EmailField::new('email')->setRequired(true);
        $phone = TelephoneField::new('phone');
        $status = ChoiceField::new('status')
            ->setChoices([
                'Active' => Customer::STATUS_ACTIVE,
                'Inactive' => Customer::STATUS_INACTIVE,
                'Blocked' => Customer::STATUS_BLOCKED
            ])
            ->setRequired(true)
            ->formatValue(function ($value, $entity) {
                $badges = [
                    Customer::STATUS_ACTIVE => '<span class="badge badge-success">Active</span>',
                    Customer::STATUS_INACTIVE => '<span class="badge badge-warning">Inactive</span>',
                    Customer::STATUS_BLOCKED => '<span class="badge badge-danger">Blocked</span>'
                ];
                return $badges[$value] ?? $value;
            });
        $createdAt = DateTimeField::new('createdAt')
            ->hideOnForm()
            ->setFormat('medium');
        $updatedAt = DateTimeField::new('updatedAt')
            ->hideOnForm()
            ->setFormat('medium');

        // Full name field for display
        $fullName = TextField::new('fullName')
            ->setLabel('Full Name')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                return $entity->getFullName();
            });

        // Address fields for detail and form pages
        $billingAddress = AssociationField::new('billingAddress')
            ->setLabel('Billing Address')
            ->setHelp('Customer\'s billing address for invoicing')
            ->formatValue(function ($value, $entity) {
                if ($value) {
                    $html = '<div class="address-display">';
                    $html .= '<strong>' . $value->getFullName() . '</strong><br>';
                    if ($value->getCompany()) {
                        $html .= $value->getCompany() . '<br>';
                    }
                    $html .= $value->getAddressLine1() . '<br>';
                    if ($value->getAddressLine2()) {
                        $html .= $value->getAddressLine2() . '<br>';
                    }
                    $html .= $value->getCity() . ', ' . $value->getState() . ' ' . $value->getPostalCode() . '<br>';
                    $html .= $value->getCountry();
                    if ($value->getPhone()) {
                        $html .= '<br><small>Phone: ' . $value->getPhone() . '</small>';
                    }
                    $html .= '</div>';
                    return $html;
                }
                return '<em class="text-muted">No billing address</em>';
            });
        
        $shippingAddress = AssociationField::new('shippingAddress')
            ->setLabel('Shipping Address')
            ->setHelp('Customer\'s default shipping address')
            ->formatValue(function ($value, $entity) {
                if ($value) {
                    $html = '<div class="address-display">';
                    $html .= '<strong>' . $value->getFullName() . '</strong><br>';
                    if ($value->getCompany()) {
                        $html .= $value->getCompany() . '<br>';
                    }
                    $html .= $value->getAddressLine1() . '<br>';
                    if ($value->getAddressLine2()) {
                        $html .= $value->getAddressLine2() . '<br>';
                    }
                    $html .= $value->getCity() . ', ' . $value->getState() . ' ' . $value->getPostalCode() . '<br>';
                    $html .= $value->getCountry();
                    if ($value->getPhone()) {
                        $html .= '<br><small>Phone: ' . $value->getPhone() . '</small>';
                    }
                    $html .= '</div>';
                    return $html;
                }
                return '<em class="text-muted">No shipping address</em>';
            });

        // Orders field for detail page - enhanced display
        $orders = AssociationField::new('orders')
            ->setLabel('Order History')
            ->formatValue(function ($value, $entity) {
                $orders = $entity->getOrders();
                $orderCount = $orders->count();
                
                if ($orderCount === 0) {
                    return '<span class="badge badge-secondary">No orders</span>';
                }
                
                // Calculate total spent
                $totalSpent = 0;
                $recentOrders = [];
                
                foreach ($orders as $order) {
                    $totalSpent += (float) $order->getTotalAmount();
                    $recentOrders[] = [
                        'orderNumber' => $order->getOrderNumber(),
                        'status' => $order->getStatus(),
                        'totalAmount' => $order->getTotalAmount(),
                        'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i')
                    ];
                }
                
                // Sort by creation date descending and take first 5
                usort($recentOrders, function($a, $b) {
                    return strcmp($b['createdAt'], $a['createdAt']);
                });
                $recentOrders = array_slice($recentOrders, 0, 5);
                
                $html = sprintf('<div class="customer-orders-summary">');
                $html .= sprintf('<div class="mb-2"><span class="badge badge-info">%d orders</span> ', $orderCount);
                $html .= sprintf('<span class="badge badge-success">$%.2f total</span></div>', $totalSpent);
                
                if (!empty($recentOrders)) {
                    $html .= '<div class="recent-orders"><strong>Recent Orders:</strong><ul class="list-unstyled mt-1">';
                    foreach ($recentOrders as $order) {
                        $statusBadge = $this->getOrderStatusBadge($order['status']);
                        $html .= sprintf(
                            '<li class="mb-1"><small>%s - $%.2f %s <em>(%s)</em></small></li>',
                            $order['orderNumber'],
                            $order['totalAmount'],
                            $statusBadge,
                            $order['createdAt']
                        );
                    }
                    $html .= '</ul></div>';
                }
                
                $html .= '</div>';
                return $html;
            });

        // Order count field for index page
        $orderCount = TextField::new('orderCount')
            ->setLabel('Orders')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                $count = $entity->getOrders()->count();
                if ($count > 0) {
                    return sprintf('<span class="badge badge-info">%d</span>', $count);
                }
                return '<span class="badge badge-secondary">0</span>';
            });

        // Configure fields based on page
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $fullName,
                $email,
                $phone,
                $status,
                $orderCount,
                $createdAt,
            ];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                FormField::addTab('Customer Information'),
                $firstName,
                $lastName,
                $email,
                $phone,
                $status,
                $createdAt,
                $updatedAt,
                
                FormField::addTab('Addresses'),
                $billingAddress,
                $shippingAddress,
                
                FormField::addTab('Order History'),
                $orders,
            ];
        } elseif (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            return [
                FormField::addTab('Customer Information'),
                $firstName,
                $lastName,
                $email,
                $phone,
                $status,
                
                FormField::addTab('Addresses'),
                $billingAddress,
                $shippingAddress,
            ];
        }

        return [];
    }

    private function getOrderStatusBadge(string $status): string
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'processing' => '<span class="badge badge-info">Processing</span>',
            'shipped' => '<span class="badge badge-primary">Shipped</span>',
            'delivered' => '<span class="badge badge-success">Delivered</span>',
            'cancelled' => '<span class="badge badge-danger">Cancelled</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Active' => Customer::STATUS_ACTIVE,
                'Inactive' => Customer::STATUS_INACTIVE,
                'Blocked' => Customer::STATUS_BLOCKED
            ])->setLabel('Status'))
            ->add(TextFilter::new('firstName')->setLabel('First Name'))
            ->add(TextFilter::new('lastName')->setLabel('Last Name'))
            ->add(TextFilter::new('email')->setLabel('Email'))
            ->add(TextFilter::new('phone')->setLabel('Phone'))
            ->add(DateTimeFilter::new('createdAt')->setLabel('Registration Date'));
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        // Enhanced search functionality - search across name, email, and phone
        $searchQuery = $searchDto->getQuery();
        if ($searchQuery) {
            $queryBuilder
                ->andWhere('entity.firstName LIKE :search OR entity.lastName LIKE :search OR entity.email LIKE :search OR entity.phone LIKE :search')
                ->setParameter('search', '%' . $searchQuery . '%');
        }
        
        return $queryBuilder;
    }

    #[IsGranted(AdminSectionVoter::MANAGE_CUSTOMERS)]
    public function toggleCustomerStatus(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $customer = $this->getContext()->getEntity()->getInstance();
        
        if (!$customer instanceof Customer) {
            throw $this->createNotFoundException('Customer not found');
        }

        // Toggle between active and inactive (don't auto-toggle to blocked)
        if ($customer->getStatus() === Customer::STATUS_ACTIVE) {
            $customer->setStatus(Customer::STATUS_INACTIVE);
            $this->addFlash('success', sprintf('Customer %s has been deactivated.', $customer->getFullName()));
        } else {
            $customer->setStatus(Customer::STATUS_ACTIVE);
            $this->addFlash('success', sprintf('Customer %s has been activated.', $customer->getFullName()));
        }

        $this->container->get('doctrine')->getManager()->flush();

        $url = $adminUrlGenerator
            ->setController(CustomerCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}