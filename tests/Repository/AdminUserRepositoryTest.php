<?php

namespace App\Tests\Repository;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class AdminUserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AdminUserRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(AdminUser::class);

        // Clean up database
        $this->entityManager->createQuery('DELETE FROM App\Entity\AdminUser')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createAdminUser(
        string $email,
        string $firstName = 'John',
        string $lastName = 'Doe',
        array $roles = ['ROLE_ADMIN'],
        string $status = 'active'
    ): AdminUser {
        $adminUser = new AdminUser();
        $adminUser->setEmail($email)
            ->setPassword('hashed_password')
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRoles($roles)
            ->setStatus($status);

        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        return $adminUser;
    }

    public function testFindByEmail(): void
    {
        $adminUser = $this->createAdminUser('admin@example.com');

        $found = $this->repository->findByEmail('admin@example.com');
        $this->assertNotNull($found);
        $this->assertEquals($adminUser->getId(), $found->getId());

        $notFound = $this->repository->findByEmail('nonexistent@example.com');
        $this->assertNull($notFound);
    }

    public function testFindActiveByEmail(): void
    {
        $activeUser = $this->createAdminUser('active@example.com', 'Active', 'User', ['ROLE_ADMIN'], 'active');
        $inactiveUser = $this->createAdminUser('inactive@example.com', 'Inactive', 'User', ['ROLE_ADMIN'], 'inactive');

        $found = $this->repository->findActiveByEmail('active@example.com');
        $this->assertNotNull($found);
        $this->assertEquals($activeUser->getId(), $found->getId());

        $notFound = $this->repository->findActiveByEmail('inactive@example.com');
        $this->assertNull($notFound);
    }

    public function testFindActive(): void
    {
        $this->createAdminUser('active1@example.com', 'Alice', 'Smith', ['ROLE_ADMIN'], 'active');
        $this->createAdminUser('active2@example.com', 'Bob', 'Johnson', ['ROLE_MANAGER'], 'active');
        $this->createAdminUser('inactive@example.com', 'Charlie', 'Brown', ['ROLE_OPERATOR'], 'inactive');

        $activeUsers = $this->repository->findActive();
        $this->assertCount(2, $activeUsers);
        
        // Should be ordered by lastName, firstName
        $this->assertEquals('Bob', $activeUsers[0]->getFirstName());
        $this->assertEquals('Alice', $activeUsers[1]->getFirstName());
    }

    public function testFindByRole(): void
    {
        $this->createAdminUser('admin@example.com', 'Admin', 'User', ['ROLE_ADMIN']);
        $this->createAdminUser('manager@example.com', 'Manager', 'User', ['ROLE_MANAGER']);
        $this->createAdminUser('operator@example.com', 'Operator', 'User', ['ROLE_OPERATOR']);
        $this->createAdminUser('multi@example.com', 'Multi', 'User', ['ROLE_ADMIN', 'ROLE_MANAGER']);

        $admins = $this->repository->findByRole('ROLE_ADMIN');
        $this->assertCount(2, $admins); // admin and multi users

        $managers = $this->repository->findByRole('ROLE_MANAGER');
        $this->assertCount(2, $managers); // manager and multi users

        $operators = $this->repository->findByRole('ROLE_OPERATOR');
        $this->assertCount(1, $operators); // only operator user
    }

    public function testFindActiveByRole(): void
    {
        $this->createAdminUser('active_admin@example.com', 'Active', 'Admin', ['ROLE_ADMIN'], 'active');
        $this->createAdminUser('inactive_admin@example.com', 'Inactive', 'Admin', ['ROLE_ADMIN'], 'inactive');

        $activeAdmins = $this->repository->findActiveByRole('ROLE_ADMIN');
        $this->assertCount(1, $activeAdmins);
        $this->assertEquals('Active', $activeAdmins[0]->getFirstName());
    }

    public function testSearch(): void
    {
        $this->createAdminUser('john.doe@example.com', 'John', 'Doe');
        $this->createAdminUser('jane.smith@example.com', 'Jane', 'Smith');
        $this->createAdminUser('bob.johnson@example.com', 'Bob', 'Johnson');

        // Search by first name
        $results = $this->repository->search('John');
        $this->assertCount(2, $results); // John Doe and Bob Johnson

        // Search by last name
        $results = $this->repository->search('Smith');
        $this->assertCount(1, $results);
        $this->assertEquals('Jane', $results[0]->getFirstName());

        // Search by email
        $results = $this->repository->search('jane.smith');
        $this->assertCount(1, $results);
        $this->assertEquals('Jane', $results[0]->getFirstName());

        // Search by full name
        $results = $this->repository->search('John Doe');
        $this->assertCount(1, $results);
        $this->assertEquals('John', $results[0]->getFirstName());
    }

    public function testFindRecentlyActive(): void
    {
        $user1 = $this->createAdminUser('recent@example.com', 'Recent', 'User');
        $user2 = $this->createAdminUser('old@example.com', 'Old', 'User');
        $user3 = $this->createAdminUser('inactive@example.com', 'Inactive', 'User', ['ROLE_ADMIN'], 'inactive');

        // Set login times
        $recentTime = new \DateTime('-1 hour');
        $oldTime = new \DateTime('-1 week');
        
        $user1->setLastLoginAt($recentTime);
        $user2->setLastLoginAt($oldTime);
        $user3->setLastLoginAt($recentTime); // Inactive user with recent login

        $this->entityManager->flush();

        $since = new \DateTime('-2 hours');
        $recentlyActive = $this->repository->findRecentlyActive($since);
        
        $this->assertCount(1, $recentlyActive); // Only active user with recent login
        $this->assertEquals('Recent', $recentlyActive[0]->getFirstName());
    }

    public function testCountByStatus(): void
    {
        $this->createAdminUser('active1@example.com', 'Active1', 'User', ['ROLE_ADMIN'], 'active');
        $this->createAdminUser('active2@example.com', 'Active2', 'User', ['ROLE_ADMIN'], 'active');
        $this->createAdminUser('inactive@example.com', 'Inactive', 'User', ['ROLE_ADMIN'], 'inactive');

        $activeCount = $this->repository->countByStatus('active');
        $this->assertEquals(2, $activeCount);

        $inactiveCount = $this->repository->countByStatus('inactive');
        $this->assertEquals(1, $inactiveCount);
    }

    public function testCountByRole(): void
    {
        $this->createAdminUser('admin1@example.com', 'Admin1', 'User', ['ROLE_ADMIN']);
        $this->createAdminUser('admin2@example.com', 'Admin2', 'User', ['ROLE_ADMIN']);
        $this->createAdminUser('manager@example.com', 'Manager', 'User', ['ROLE_MANAGER']);
        $this->createAdminUser('multi@example.com', 'Multi', 'User', ['ROLE_ADMIN', 'ROLE_MANAGER']);

        $adminCount = $this->repository->countByRole('ROLE_ADMIN');
        $this->assertEquals(3, $adminCount); // admin1, admin2, multi

        $managerCount = $this->repository->countByRole('ROLE_MANAGER');
        $this->assertEquals(2, $managerCount); // manager, multi

        $operatorCount = $this->repository->countByRole('ROLE_OPERATOR');
        $this->assertEquals(0, $operatorCount);
    }

    public function testFindCreatedBetween(): void
    {
        $start = new \DateTime('-1 week');
        $end = new \DateTime('+1 week');
        
        $user1 = $this->createAdminUser('user1@example.com', 'User1', 'Test');
        $user2 = $this->createAdminUser('user2@example.com', 'User2', 'Test');

        $results = $this->repository->findCreatedBetween($start, $end);
        $this->assertCount(2, $results);

        // Test with narrow range that excludes all users
        $futureStart = new \DateTime('+1 day');
        $futureEnd = new \DateTime('+2 days');
        $results = $this->repository->findCreatedBetween($futureStart, $futureEnd);
        $this->assertCount(0, $results);
    }

    public function testUpdateLastLogin(): void
    {
        $adminUser = $this->createAdminUser('test@example.com');
        $this->assertNull($adminUser->getLastLoginAt());

        $this->repository->updateLastLogin($adminUser);
        
        $this->entityManager->refresh($adminUser);
        $this->assertNotNull($adminUser->getLastLoginAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $adminUser->getLastLoginAt());
    }

    public function testSaveAndRemove(): void
    {
        $adminUser = new AdminUser();
        $adminUser->setEmail('test@example.com')
            ->setPassword('hashed_password')
            ->setFirstName('Test')
            ->setLastName('User')
            ->setRoles(['ROLE_ADMIN']);

        // Test save without flush
        $this->repository->save($adminUser, false);
        $this->assertTrue($this->entityManager->contains($adminUser));

        // Test save with flush
        $this->repository->save($adminUser, true);
        $this->assertNotNull($adminUser->getId());

        $id = $adminUser->getId();

        // Test remove
        $this->repository->remove($adminUser, true);
        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testUpgradePassword(): void
    {
        $adminUser = $this->createAdminUser('test@example.com');
        $oldPassword = $adminUser->getPassword();
        $newPassword = 'new_hashed_password';

        $this->repository->upgradePassword($adminUser, $newPassword);
        
        $this->assertEquals($newPassword, $adminUser->getPassword());
        $this->assertNotEquals($oldPassword, $adminUser->getPassword());
    }

    public function testUpgradePasswordWithInvalidUser(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $invalidUser = $this->createMock(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class);
        $this->repository->upgradePassword($invalidUser, 'new_password');
    }
}