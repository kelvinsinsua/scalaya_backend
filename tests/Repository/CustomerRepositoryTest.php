<?php

namespace App\Tests\Repository;

use App\Entity\Address;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CustomerRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CustomerRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Customer::class);
        
        // Clean up database
        $this->entityManager->createQuery('DELETE FROM App\Entity\Customer')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createCustomer(string $email, string $firstName, string $lastName, string $status = Customer::STATUS_ACTIVE): Customer
    {
        $customer = new Customer();
        $customer->setEmail($email)
                 ->setFirstName($firstName)
                 ->setLastName($lastName)
                 ->setStatus($status);
        
        $this->entityManager->persist($customer);
        $this->entityManager->flush();
        
        return $customer;
    }

    public function testSave(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com')
                 ->setFirstName('Test')
                 ->setLastName('User')
                 ->setStatus(Customer::STATUS_ACTIVE);

        $this->repository->save($customer, true);

        $this->assertNotNull($customer->getId());
        
        $foundCustomer = $this->repository->find($customer->getId());
        $this->assertSame($customer, $foundCustomer);
    }

    public function testRemove(): void
    {
        $customer = $this->createCustomer('test@example.com', 'Test', 'User');
        $customerId = $customer->getId();

        $this->repository->remove($customer, true);

        $foundCustomer = $this->repository->find($customerId);
        $this->assertNull($foundCustomer);
    }

    public function testFindByStatus(): void
    {
        $activeCustomer = $this->createCustomer('active@example.com', 'Active', 'User', Customer::STATUS_ACTIVE);
        $inactiveCustomer = $this->createCustomer('inactive@example.com', 'Inactive', 'User', Customer::STATUS_INACTIVE);
        $blockedCustomer = $this->createCustomer('blocked@example.com', 'Blocked', 'User', Customer::STATUS_BLOCKED);

        $activeCustomers = $this->repository->findByStatus(Customer::STATUS_ACTIVE);
        $this->assertCount(1, $activeCustomers);
        $this->assertSame($activeCustomer, $activeCustomers[0]);

        $inactiveCustomers = $this->repository->findByStatus(Customer::STATUS_INACTIVE);
        $this->assertCount(1, $inactiveCustomers);
        $this->assertSame($inactiveCustomer, $inactiveCustomers[0]);

        $blockedCustomers = $this->repository->findByStatus(Customer::STATUS_BLOCKED);
        $this->assertCount(1, $blockedCustomers);
        $this->assertSame($blockedCustomer, $blockedCustomers[0]);
    }

    public function testFindActive(): void
    {
        $activeCustomer1 = $this->createCustomer('active1@example.com', 'Active1', 'User', Customer::STATUS_ACTIVE);
        $activeCustomer2 = $this->createCustomer('active2@example.com', 'Active2', 'User', Customer::STATUS_ACTIVE);
        $inactiveCustomer = $this->createCustomer('inactive@example.com', 'Inactive', 'User', Customer::STATUS_INACTIVE);

        $activeCustomers = $this->repository->findActive();
        $this->assertCount(2, $activeCustomers);
        
        $activeIds = array_map(fn($c) => $c->getId(), $activeCustomers);
        $this->assertContains($activeCustomer1->getId(), $activeIds);
        $this->assertContains($activeCustomer2->getId(), $activeIds);
        $this->assertNotContains($inactiveCustomer->getId(), $activeIds);
    }

    public function testSearchByEmail(): void
    {
        $customer1 = $this->createCustomer('john.doe@example.com', 'John', 'Doe');
        $customer2 = $this->createCustomer('jane.doe@example.com', 'Jane', 'Doe');
        $customer3 = $this->createCustomer('bob.smith@test.com', 'Bob', 'Smith');

        $results = $this->repository->searchByEmail('doe');
        $this->assertCount(2, $results);
        
        $resultIds = array_map(fn($c) => $c->getId(), $results);
        $this->assertContains($customer1->getId(), $resultIds);
        $this->assertContains($customer2->getId(), $resultIds);
        $this->assertNotContains($customer3->getId(), $resultIds);

        $results = $this->repository->searchByEmail('john');
        $this->assertCount(1, $results);
        $this->assertSame($customer1, $results[0]);
    }

    public function testSearchByName(): void
    {
        $customer1 = $this->createCustomer('john.doe@example.com', 'John', 'Doe');
        $customer2 = $this->createCustomer('jane.doe@example.com', 'Jane', 'Doe');
        $customer3 = $this->createCustomer('bob.smith@test.com', 'Bob', 'Smith');

        $results = $this->repository->searchByName('Doe');
        $this->assertCount(2, $results);
        
        $resultIds = array_map(fn($c) => $c->getId(), $results);
        $this->assertContains($customer1->getId(), $resultIds);
        $this->assertContains($customer2->getId(), $resultIds);
        $this->assertNotContains($customer3->getId(), $resultIds);

        $results = $this->repository->searchByName('John');
        $this->assertCount(1, $results);
        $this->assertSame($customer1, $results[0]);
    }

    public function testSearch(): void
    {
        $customer1 = $this->createCustomer('john.doe@example.com', 'John', 'Doe');
        $customer2 = $this->createCustomer('jane.doe@example.com', 'Jane', 'Doe');
        $customer3 = $this->createCustomer('bob.smith@test.com', 'Bob', 'Smith');

        // Search by email
        $results = $this->repository->search('john.doe');
        $this->assertCount(1, $results);
        $this->assertSame($customer1, $results[0]);

        // Search by first name
        $results = $this->repository->search('Jane');
        $this->assertCount(1, $results);
        $this->assertSame($customer2, $results[0]);

        // Search by last name
        $results = $this->repository->search('Smith');
        $this->assertCount(1, $results);
        $this->assertSame($customer3, $results[0]);

        // Search that matches multiple
        $results = $this->repository->search('Doe');
        $this->assertCount(2, $results);
    }

    public function testFindWithFilters(): void
    {
        $activeCustomer = $this->createCustomer('active@example.com', 'Active', 'User', Customer::STATUS_ACTIVE);
        $inactiveCustomer = $this->createCustomer('inactive@example.com', 'Inactive', 'User', Customer::STATUS_INACTIVE);
        $johnCustomer = $this->createCustomer('john@example.com', 'John', 'Doe', Customer::STATUS_ACTIVE);

        // Filter by status
        $results = $this->repository->findWithFilters(['status' => Customer::STATUS_ACTIVE]);
        $this->assertCount(2, $results);

        // Filter by search term
        $results = $this->repository->findWithFilters(['search' => 'john']);
        $this->assertCount(1, $results);
        $this->assertSame($johnCustomer, $results[0]);

        // Filter by email
        $results = $this->repository->findWithFilters(['email' => 'active']);
        $this->assertCount(1, $results);
        $this->assertSame($activeCustomer, $results[0]);

        // Filter by name
        $results = $this->repository->findWithFilters(['name' => 'Doe']);
        $this->assertCount(1, $results);
        $this->assertSame($johnCustomer, $results[0]);

        // Multiple filters
        $results = $this->repository->findWithFilters([
            'status' => Customer::STATUS_ACTIVE,
            'search' => 'john'
        ]);
        $this->assertCount(1, $results);
        $this->assertSame($johnCustomer, $results[0]);
    }

    public function testFindByEmail(): void
    {
        $customer = $this->createCustomer('unique@example.com', 'Unique', 'User');

        $foundCustomer = $this->repository->findByEmail('unique@example.com');
        $this->assertSame($customer, $foundCustomer);

        $notFoundCustomer = $this->repository->findByEmail('nonexistent@example.com');
        $this->assertNull($notFoundCustomer);
    }

    public function testCountByStatus(): void
    {
        $this->createCustomer('active1@example.com', 'Active1', 'User', Customer::STATUS_ACTIVE);
        $this->createCustomer('active2@example.com', 'Active2', 'User', Customer::STATUS_ACTIVE);
        $this->createCustomer('inactive@example.com', 'Inactive', 'User', Customer::STATUS_INACTIVE);

        $activeCount = $this->repository->countByStatus(Customer::STATUS_ACTIVE);
        $this->assertEquals(2, $activeCount);

        $inactiveCount = $this->repository->countByStatus(Customer::STATUS_INACTIVE);
        $this->assertEquals(1, $inactiveCount);

        $blockedCount = $this->repository->countByStatus(Customer::STATUS_BLOCKED);
        $this->assertEquals(0, $blockedCount);
    }

    public function testFindWithAddresses(): void
    {
        $customer = $this->createCustomer('test@example.com', 'Test', 'User');
        
        $billingAddress = new Address();
        $billingAddress->setFirstName('Test')
                      ->setLastName('User')
                      ->setAddressLine1('123 Main St')
                      ->setCity('Test City')
                      ->setState('Test State')
                      ->setPostalCode('12345')
                      ->setCountry('US');
        
        $customer->setBillingAddress($billingAddress);
        $this->entityManager->persist($billingAddress);
        $this->entityManager->flush();

        $results = $this->repository->findWithAddresses();
        $this->assertCount(1, $results);
        
        $foundCustomer = $results[0];
        $this->assertSame($customer, $foundCustomer);
        $this->assertNotNull($foundCustomer->getBillingAddress());
    }

    public function testFindCreatedBetween(): void
    {
        $startDate = new \DateTimeImmutable('-2 days');
        $endDate = new \DateTimeImmutable('+1 day');
        
        $customer1 = $this->createCustomer('test1@example.com', 'Test1', 'User');
        $customer2 = $this->createCustomer('test2@example.com', 'Test2', 'User');

        $results = $this->repository->findCreatedBetween($startDate, $endDate);
        $this->assertCount(2, $results);
        
        $resultIds = array_map(fn($c) => $c->getId(), $results);
        $this->assertContains($customer1->getId(), $resultIds);
        $this->assertContains($customer2->getId(), $resultIds);

        // Test with narrow date range that excludes all customers
        $futureStart = new \DateTimeImmutable('+1 day');
        $futureEnd = new \DateTimeImmutable('+2 days');
        
        $results = $this->repository->findCreatedBetween($futureStart, $futureEnd);
        $this->assertCount(0, $results);
    }

    public function testOrderingInResults(): void
    {
        // Create customers in specific order to test sorting
        $customerZ = $this->createCustomer('z@example.com', 'Alice', 'Zulu');
        $customerA = $this->createCustomer('a@example.com', 'Bob', 'Alpha');
        $customerB = $this->createCustomer('b@example.com', 'Charlie', 'Beta');

        $results = $this->repository->findActive();
        
        // Should be ordered by lastName ASC, then firstName ASC
        $this->assertCount(3, $results);
        $this->assertSame($customerA, $results[0]); // Alpha
        $this->assertSame($customerB, $results[1]); // Beta
        $this->assertSame($customerZ, $results[2]); // Zulu
    }
}