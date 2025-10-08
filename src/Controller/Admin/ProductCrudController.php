<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Security\AdminSectionVoter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(AdminSectionVoter::VIEW_PRODUCTS)]
class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Product')
            ->setEntityLabelInPlural('Products')
            ->setSearchFields(['name', 'sku', 'category', 'supplier.companyName'])
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

        // Only allow create, edit, delete if user has MANAGE_PRODUCTS permission
        if ($this->isGranted(AdminSectionVoter::MANAGE_PRODUCTS)) {
            $actions
                ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                    return $action->setIcon('fa fa-plus')->setLabel('Add Product');
                })
                ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                    return $action->setIcon('fa fa-edit');
                })
                ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                    return $action->setIcon('fa fa-trash');
                });
        } else {
            // Remove create, edit, delete actions for users without MANAGE_PRODUCTS
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
        $name = TextField::new('name')->setRequired(true);
        $sku = TextField::new('sku')->setRequired(true);
        $sellingPrice = MoneyField::new('sellingPrice')
            ->setCurrency('USD')
            ->setRequired(true)
            ->setNumDecimals(2);
        $stockLevel = IntegerField::new('stockLevel')
            ->setRequired(true)
            ->setHelp('Current stock quantity')
            ->formatValue(function ($value, $entity) {
                if ($value <= 0) {
                    return sprintf('<span class="badge badge-danger">%d</span>', $value);
                } elseif ($value <= 10) {
                    return sprintf('<span class="badge badge-warning">%d</span>', $value);
                } else {
                    return sprintf('<span class="badge badge-success">%d</span>', $value);
                }
            });
        $status = ChoiceField::new('status')
            ->setChoices([
                'Available' => 'available',
                'Out of Stock' => 'out_of_stock',
                'Discontinued' => 'discontinued'
            ])
            ->setRequired(true)
            ->formatValue(function ($value, $entity) {
                $badges = [
                    'available' => '<span class="badge badge-success">Available</span>',
                    'out_of_stock' => '<span class="badge badge-warning">Out of Stock</span>',
                    'discontinued' => '<span class="badge badge-danger">Discontinued</span>'
                ];
                return $badges[$value] ?? $value;
            });
        $supplier = AssociationField::new('supplier')
            ->setRequired(true)
            ->formatValue(function ($value, $entity) {
                if ($value) {
                    return sprintf('%s (%s)', $value->getCompanyName(), $value->getStatus());
                }
                return '';
            });
        $category = TextField::new('category');
        $createdAt = DateTimeField::new('createdAt')
            ->hideOnForm()
            ->setFormat('medium');
        $updatedAt = DateTimeField::new('updatedAt')
            ->hideOnForm()
            ->setFormat('medium');

        // Stock status indicator field
        $stockStatus = BooleanField::new('isInStock')
            ->setLabel('In Stock')
            ->hideOnForm()
            ->renderAsSwitch(false);

        // Additional fields for detail and form pages
        $supplierReference = TextField::new('supplierReference')
            ->setRequired(true)
            ->setHelp('Supplier\'s product reference/SKU');
        $description = TextareaField::new('description')
            ->setNumOfRows(4);
        $costPrice = MoneyField::new('costPrice')
            ->setCurrency('USD')
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setHelp('Cost price from supplier');
        $weight = NumberField::new('weight')
            ->setNumDecimals(3)
            ->setHelp('Weight in kg');
        $dimensions = ArrayField::new('dimensions')
            ->setHelp('Dimensions in cm (width, height, depth)');
        $images = ArrayField::new('images')
            ->setHelp('Product image URLs');

        // Configure fields based on page
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $name,
                $sku,
                $sellingPrice,
                $stockLevel,
                $stockStatus,
                $status,
                $supplier,
                $category,
                $createdAt,
            ];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $name,
                $sku,
                $supplierReference,
                $description,
                $images,
                $costPrice,
                $sellingPrice,
                $weight,
                $dimensions,
                $category,
                $stockLevel,
                $status,
                $supplier,
                $createdAt,
                $updatedAt,
            ];
        } elseif (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            return [
                $name,
                $sku,
                $supplierReference,
                $description,
                $images,
                $costPrice,
                $sellingPrice,
                $weight,
                $dimensions,
                $category,
                $stockLevel,
                $status,
                $supplier,
            ];
        }

        return [];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('supplier')->setLabel('Supplier'))
            ->add(ChoiceFilter::new('status')->setChoices([
                'Available' => 'available',
                'Out of Stock' => 'out_of_stock',
                'Discontinued' => 'discontinued'
            ])->setLabel('Status'))
            ->add(TextFilter::new('category')->setLabel('Category'))
            ->add(TextFilter::new('name')->setLabel('Product Name'))
            ->add(TextFilter::new('sku')->setLabel('SKU'))
            ->add(NumericFilter::new('stockLevel')->setLabel('Stock Level'))
            ->add(NumericFilter::new('sellingPrice')->setLabel('Selling Price'));
    }
}