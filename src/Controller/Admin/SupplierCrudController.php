<?php

namespace App\Controller\Admin;

use App\Entity\Supplier;
use App\Security\AdminSectionVoter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted(AdminSectionVoter::VIEW_SUPPLIERS)]
class SupplierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Supplier::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Supplier')
            ->setEntityLabelInPlural('Suppliers')
            ->setSearchFields(['companyName', 'contactEmail', 'contactPerson', 'phone'])
            ->setDefaultSort(['companyName' => 'ASC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }



    public function configureFields(string $pageName): iterable
    {
        // Common fields for all pages
        $id = IdField::new('id')->hideOnForm();
        $companyName = TextField::new('companyName')
            ->setLabel('Company Name')
            ->setRequired(true);
        $contactEmail = EmailField::new('contactEmail')
            ->setLabel('Contact Email')
            ->setRequired(true);
        $contactPerson = TextField::new('contactPerson')
            ->setLabel('Contact Person');
        $phone = TelephoneField::new('phone')
            ->setLabel('Phone Number');
        $status = ChoiceField::new('status')
            ->setChoices([
                'Active' => 'active',
                'Inactive' => 'inactive'
            ])
            ->setRequired(true)
            ->formatValue(function ($value, $entity) {
                $productCount = $entity->getProducts()->count();
                $activeProducts = $entity->getProducts()->filter(function($product) {
                    return $product->getStatus() === 'available';
                })->count();
                
                if ($value === 'active') {
                    $badge = '<span class="badge badge-success">Active</span>';
                    if ($productCount > 0) {
                        $badge .= sprintf(' <small class="text-muted">(%d/%d products active)</small>', $activeProducts, $productCount);
                    }
                } else {
                    $badge = '<span class="badge badge-danger">Inactive</span>';
                    if ($activeProducts > 0) {
                        $badge .= sprintf(' <small class="text-warning">(%d active products affected)</small>', $activeProducts);
                    }
                }
                
                return $badge;
            });
        $createdAt = DateTimeField::new('createdAt')
            ->hideOnForm()
            ->setFormat('medium');
        $updatedAt = DateTimeField::new('updatedAt')
            ->hideOnForm()
            ->setFormat('medium');

        // Product count field (calculated)
        $productCount = IntegerField::new('productCount')
            ->setLabel('Products')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                $count = $entity->getProducts()->count();
                if ($count > 0) {
                    return sprintf('<span class="badge badge-info">%d</span>', $count);
                }
                return '<span class="badge badge-secondary">0</span>';
            });

        // Additional fields for detail and form pages
        $address = TextareaField::new('address')
            ->setLabel('Address')
            ->setNumOfRows(3);
        $notes = TextareaField::new('notes')
            ->setLabel('Notes')
            ->setNumOfRows(4)
            ->setHelp('Internal notes about this supplier');
        $products = AssociationField::new('products')
            ->setLabel('Associated Products')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                $products = $entity->getProducts();
                if ($products->count() === 0) {
                    return '<em>No products associated with this supplier</em>';
                }
                
                $activeCount = 0;
                $inactiveCount = 0;
                $productList = [];
                
                foreach ($products as $product) {
                    if ($product->getStatus() === 'available') {
                        $activeCount++;
                        $statusBadge = 'success';
                        $statusText = 'Available';
                    } elseif ($product->getStatus() === 'out_of_stock') {
                        $inactiveCount++;
                        $statusBadge = 'warning';
                        $statusText = 'Out of Stock';
                    } else {
                        $inactiveCount++;
                        $statusBadge = 'danger';
                        $statusText = 'Discontinued';
                    }
                    
                    $productList[] = sprintf(
                        '<div class="mb-1"><span class="badge badge-%s">%s</span> %s (Stock: %d)</div>',
                        $statusBadge,
                        $statusText,
                        $product->getName(),
                        $product->getStockLevel()
                    );
                }
                
                $summary = sprintf(
                    '<div class="mb-2"><strong>Summary:</strong> %d total products (%d active, %d inactive)</div>',
                    $products->count(),
                    $activeCount,
                    $inactiveCount
                );
                
                $displayProducts = array_slice($productList, 0, 10);
                $productDisplay = implode('', $displayProducts);
                
                if ($products->count() > 10) {
                    $productDisplay .= sprintf('<div class="text-muted"><em>... and %d more products</em></div>', $products->count() - 10);
                }
                
                return $summary . $productDisplay;
            });

        // Configure fields based on page
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $companyName,
                $contactEmail,
                $contactPerson,
                $phone,
                $productCount,
                $status,
                $createdAt,
            ];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $companyName,
                $contactEmail,
                $contactPerson,
                $phone,
                $address,
                $notes,
                $status,
                $products,
                $createdAt,
                $updatedAt,
            ];
        } elseif (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            // Add help text for status field in forms
            $statusWithHelp = ChoiceField::new('status')
                ->setChoices([
                    'Active' => 'active',
                    'Inactive' => 'inactive'
                ])
                ->setRequired(true)
                ->setHelp('Warning: Setting status to "Inactive" may affect associated products and their availability.');
            
            return [
                $companyName,
                $contactEmail,
                $contactPerson,
                $phone,
                $address,
                $notes,
                $statusWithHelp,
            ];
        }

        return [];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Active' => 'active',
                'Inactive' => 'inactive'
            ])->setLabel('Status'))
            ->add(TextFilter::new('companyName')->setLabel('Company Name'))
            ->add(TextFilter::new('contactEmail')->setLabel('Contact Email'))
            ->add(TextFilter::new('contactPerson')->setLabel('Contact Person'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye');
            });

        // Only allow management actions if user has MANAGE_SUPPLIERS permission
        if ($this->isGranted(AdminSectionVoter::MANAGE_SUPPLIERS)) {
            $toggleStatus = Action::new('toggleStatus', 'Toggle Status', 'fa fa-toggle-on')
                ->linkToCrudAction('toggleSupplierStatus')
                ->displayIf(function ($entity) {
                    return $entity instanceof Supplier;
                });

            $actions
                ->add(Crud::PAGE_INDEX, $toggleStatus)
                ->add(Crud::PAGE_DETAIL, $toggleStatus)
                ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                    return $action->setIcon('fa fa-plus')->setLabel('Add Supplier');
                })
                ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                    return $action->setIcon('fa fa-edit');
                })
                ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                    return $action->setIcon('fa fa-trash');
                });
        } else {
            // Remove management actions for users without MANAGE_SUPPLIERS
            $actions
                ->remove(Crud::PAGE_INDEX, Action::NEW)
                ->remove(Crud::PAGE_INDEX, Action::EDIT)
                ->remove(Crud::PAGE_INDEX, Action::DELETE)
                ->remove(Crud::PAGE_DETAIL, Action::EDIT)
                ->remove(Crud::PAGE_DETAIL, Action::DELETE);
        }

        return $actions;
    }

    #[IsGranted(AdminSectionVoter::MANAGE_SUPPLIERS)]
    public function toggleSupplierStatus(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        /** @var Supplier $supplier */
        $supplier = $context->getEntity()->getInstance();
        
        $newStatus = $supplier->getStatus() === 'active' ? 'inactive' : 'active';
        $oldStatus = $supplier->getStatus();
        
        // Check for products that will be affected if deactivating
        $affectedProducts = [];
        if ($newStatus === 'inactive') {
            foreach ($supplier->getProducts() as $product) {
                if ($product->getStatus() === 'available') {
                    $affectedProducts[] = $product->getName();
                }
            }
        }
        
        // Show warning if there are affected products
        if (!empty($affectedProducts) && $newStatus === 'inactive') {
            $productList = implode(', ', array_slice($affectedProducts, 0, 5));
            if (count($affectedProducts) > 5) {
                $productList .= sprintf(' and %d more', count($affectedProducts) - 5);
            }
            
            $this->addFlash('warning', sprintf(
                'Warning: Deactivating supplier "%s" will affect %d product(s): %s. These products may become unavailable.',
                $supplier->getCompanyName(),
                count($affectedProducts),
                $productList
            ));
        }
        
        // Update the status
        $supplier->setStatus($newStatus);
        $entityManager->flush();
        
        $statusText = $newStatus === 'active' ? 'activated' : 'deactivated';
        $this->addFlash('success', sprintf(
            'Supplier "%s" has been %s successfully.',
            $supplier->getCompanyName(),
            $statusText
        ));
        
        return $this->redirect($context->getReferrer());
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Supplier $supplier */
        $supplier = $entityInstance;
        
        // Check if status is being changed to inactive and warn about products
        $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($supplier);
        if (isset($originalData['status']) && 
            $originalData['status'] === 'active' && 
            $supplier->getStatus() === 'inactive') {
            
            $activeProducts = $supplier->getProducts()->filter(function($product) {
                return $product->getStatus() === 'available';
            });
            
            if ($activeProducts->count() > 0) {
                $this->addFlash('warning', sprintf(
                    'Supplier "%s" has been deactivated. This affects %d active product(s). Consider updating product statuses accordingly.',
                    $supplier->getCompanyName(),
                    $activeProducts->count()
                ));
            }
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }
}