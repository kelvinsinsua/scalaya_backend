<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use App\Security\AdminSectionVoter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

#[IsGranted(AdminSectionVoter::VIEW_ADMIN_USERS)]
class AdminUserCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AdminUser::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Admin User')
            ->setEntityLabelInPlural('Admin Users')
            ->setPageTitle('index', 'Admin Users')
            ->setPageTitle('new', 'Create Admin User')
            ->setPageTitle('edit', 'Edit Admin User')
            ->setPageTitle('detail', 'Admin User Details')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Custom action to toggle user status
        $toggleStatusAction = Action::new('toggleStatus', 'Toggle Status', 'fas fa-toggle-on')
            ->linkToCrudAction('toggleUserStatus')
            ->setCssClass('btn btn-warning')
            ->displayIf(function ($entity) {
                // Don't allow toggling status of the current user
                return $entity->getId() !== $this->getUser()->getId();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $toggleStatusAction)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus')->setLabel('Add Admin User');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')
                    ->displayIf(function ($entity) {
                        // Don't allow deleting the current user
                        return $entity->getId() !== $this->getUser()->getId();
                    });
            });
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [];

        // ID field - only show in detail view
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = IdField::new('id')->setLabel('ID');
        }

        // Basic information fields
        $fields[] = TextField::new('firstName')
            ->setLabel('First Name')
            ->setRequired(true);

        $fields[] = TextField::new('lastName')
            ->setLabel('Last Name')
            ->setRequired(true);

        $fields[] = EmailField::new('email')
            ->setLabel('Email')
            ->setRequired(true)
            ->setHelp('Must be unique across all admin users');

        // Password field - only show in new/edit forms
        if (in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT])) {
            $passwordField = TextField::new('password')
                ->setLabel('Password')
                ->setFormType(PasswordType::class)
                ->setHelp('Minimum 8 characters required');

            // Make password required only for new users
            if ($pageName === Crud::PAGE_NEW) {
                $passwordField->setRequired(true);
            } else {
                $passwordField->setRequired(false)
                    ->setHelp('Leave empty to keep current password');
            }

            $fields[] = $passwordField;
        }

        // Roles field with validation
        $fields[] = ChoiceField::new('roles')
            ->setLabel('Roles')
            ->setChoices([
                'Administrator' => 'ROLE_ADMIN',
                'Manager' => 'ROLE_MANAGER',
                'Operator' => 'ROLE_OPERATOR',
            ])
            ->allowMultipleChoices(true)
            ->setRequired(true)
            ->setHelp('Select one or more roles for this admin user')
            ->renderExpanded(false)
            ->renderAsBadges([
                'ROLE_ADMIN' => 'danger',
                'ROLE_MANAGER' => 'warning', 
                'ROLE_OPERATOR' => 'info',
            ]);

        // Status field
        $statusField = ChoiceField::new('status')
            ->setLabel('Status')
            ->setChoices([
                'Active' => 'active',
                'Inactive' => 'inactive',
            ])
            ->setRequired(true)
            ->renderAsBadges([
                'active' => 'success',
                'inactive' => 'danger',
            ]);

        // Add help text for status field
        if (in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT])) {
            $statusField->setHelp('Inactive users cannot log into the admin panel');
        }

        $fields[] = $statusField;

        // Timestamp fields - only show in detail view
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = DateTimeField::new('lastLoginAt')
                ->setLabel('Last Login')
                ->setFormat('medium', 'short')
                ->setHelp('When this admin user last logged in');

            $fields[] = DateTimeField::new('createdAt')
                ->setLabel('Created At')
                ->setFormat('medium', 'short');

            $fields[] = DateTimeField::new('updatedAt')
                ->setLabel('Updated At')
                ->setFormat('medium', 'short');
        }

        // Show last login in list view
        if ($pageName === Crud::PAGE_INDEX) {
            $fields[] = DateTimeField::new('lastLoginAt')
                ->setLabel('Last Login')
                ->setFormat('short', 'short')
                ->setEmptyData('Never')
                ->setHelp('When this admin user last logged in');

            $fields[] = DateTimeField::new('createdAt')
                ->setLabel('Created')
                ->setFormat('short', 'none');
        }

        return $fields;
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        /** @var AdminUser $entityInstance */
        
        // Validate email uniqueness
        $this->validateEmailUniqueness($entityInstance);
        
        // Hash the password for new users
        if ($entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPassword()
            );
            $entityInstance->setPassword($hashedPassword);
        }

        // Validate roles
        $this->validateRoles($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        /** @var AdminUser $entityInstance */
        
        // Validate email uniqueness (excluding current entity)
        $this->validateEmailUniqueness($entityInstance, $entityInstance->getId());
        
        // Hash the password only if it was changed
        $plainPassword = $entityInstance->getPassword();
        if ($plainPassword && !empty(trim($plainPassword))) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $plainPassword
            );
            $entityInstance->setPassword($hashedPassword);
        } else {
            // If password is empty, keep the existing password
            $originalEntity = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
            if (isset($originalEntity['password'])) {
                $entityInstance->setPassword($originalEntity['password']);
            }
        }

        // Validate roles
        $this->validateRoles($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Custom action to toggle user status between active and inactive
     */
    public function toggleUserStatus(): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $entityId = $request->query->get('entityId');
        
        if (!$entityId) {
            $this->addFlash('error', 'Invalid admin user ID.');
            return $this->redirectToRoute('admin');
        }

        $adminUser = $this->entityManager->getRepository(AdminUser::class)->find($entityId);
        
        if (!$adminUser) {
            $this->addFlash('error', 'Admin user not found.');
            return $this->redirectToRoute('admin');
        }

        // Prevent users from deactivating themselves
        if ($adminUser->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot change your own status.');
            return $this->redirect($request->headers->get('referer'));
        }

        // Toggle status
        $newStatus = $adminUser->getStatus() === 'active' ? 'inactive' : 'active';
        $adminUser->setStatus($newStatus);
        
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf(
            'Admin user "%s" has been %s.',
            $adminUser->getFullName(),
            $newStatus === 'active' ? 'activated' : 'deactivated'
        ));

        return $this->redirect($request->headers->get('referer'));
    }

    private function validateRoles(AdminUser $adminUser): void
    {
        $roles = $adminUser->getRoles();
        $validRoles = ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_OPERATOR', 'ROLE_USER'];
        
        // Remove ROLE_USER as it's automatically added
        $userRoles = array_filter($roles, fn($role) => $role !== 'ROLE_USER');
        
        if (empty($userRoles)) {
            throw new \InvalidArgumentException('At least one role must be assigned to the admin user.');
        }

        foreach ($userRoles as $role) {
            if (!in_array($role, $validRoles)) {
                throw new \InvalidArgumentException(sprintf('Invalid role "%s". Valid roles are: %s', $role, implode(', ', array_slice($validRoles, 0, -1))));
            }
        }
    }

    private function validateEmailUniqueness(AdminUser $adminUser, ?int $excludeId = null): void
    {
        $repository = $this->entityManager->getRepository(AdminUser::class);
        $queryBuilder = $repository->createQueryBuilder('au')
            ->where('au.email = :email')
            ->setParameter('email', $adminUser->getEmail());

        if ($excludeId) {
            $queryBuilder->andWhere('au.id != :id')
                ->setParameter('id', $excludeId);
        }

        $existingUser = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($existingUser) {
            throw new \InvalidArgumentException('An admin user with this email already exists.');
        }
    }
}