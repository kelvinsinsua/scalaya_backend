<?php

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Create a new admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin user email')
            ->addArgument('password', InputArgument::REQUIRED, 'Admin user password')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Admin user first name')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Admin user last name')
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'Admin role (ROLE_ADMIN, ROLE_MANAGER, ROLE_OPERATOR)', 'ROLE_ADMIN')
            ->addOption('status', 's', InputOption::VALUE_OPTIONAL, 'Admin status (active, inactive)', 'active')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $role = $input->getOption('role');
        $status = $input->getOption('status');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(AdminUser::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('Admin user with email "%s" already exists.', $email));
            return Command::FAILURE;
        }

        // Create new admin user
        $adminUser = new AdminUser();
        $adminUser->setEmail($email);
        $adminUser->setFirstName($firstName);
        $adminUser->setLastName($lastName);
        $adminUser->setRoles([$role]);
        $adminUser->setStatus($status);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, $password);
        $adminUser->setPassword($hashedPassword);

        // Validate the user
        $errors = $this->validator->validate($adminUser);
        if (count($errors) > 0) {
            $io->error('Validation errors:');
            foreach ($errors as $error) {
                $io->writeln('- ' . $error->getMessage());
            }
            return Command::FAILURE;
        }

        // Save to database
        try {
            $this->entityManager->persist($adminUser);
            $this->entityManager->flush();

            $io->success(sprintf(
                'Admin user created successfully!
Email: %s
Name: %s %s
Role: %s
Status: %s',
                $email,
                $firstName,
                $lastName,
                $role,
                $status
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}