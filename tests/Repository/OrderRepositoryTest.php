<?php

namespace App\Tests\Repository;

use App\Entity\Order;
use App\Entity\Customer;
use App\Entity\Address;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OrderRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Order::class);

        // Clean up database
        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderItem')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Customer')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Address')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up database
        if ($this->entityManager) {
            $this->entityManager->createQuery('DELETE FROM App\Entity\OrderItem')->execute();
            $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
            $this->entityManager->createQuery('DELETE FROM App\Entity\Customer')->execute();
            $this->entityManager->createQuery('DELETE FROM App\Entity\Address')->execute();

            $this->entityManager->close();
        }
    }

    private function createTestCustomer(string $email = 'test@example.com'): Customer
    {
        $customer = new Customer();
        $customer->setEmail($email)
                 ->setFirstName('John')
                 ->setLastName('Doe');

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    private function createTestAddress(): Address
    {
        $address = new Address();
        $address->setFirstName('John')
                ->setLastName('Doe')
                ->setAddressLine1('123 Main St')
                ->setCity('Anytown')
                ->setState('CA')
                ->setPostalCode('12345')
                ->setCountry('US');

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        return $address;
    }

    private function createTestOrder(Customer $customer, Address $address, string $status = Order::STATUS_PENDING): Order
    {
        $order = new Order();
        $order->setCustomer($customer)
              ->setShippingAddress($address)
              ->setStatus($status)
              ->setSubtotal('100.00')
              ->setTaxAmount('10.00')
              ->setShippingAmount('5.00')
              ->setTotalAmount('115.00');

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    public function testSaveAndRemove(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $order = new Order();
        $order->setCustomer($customer)
              ->setShippingAddress($address);

        // Test save
        $this->repository->save($order, true);
        $this->assertNotNull($order->getId());

        $foundOrder = $this->repository->find($order->getId());
        $this->assertSame($order, $foundOrder);

        // Test remove
        $this->repository->remove($order, true);
        $removedOrder = $this->repository->find($order->getId());
        $this->assertNull($removedOrder);
    }

    public function testFindByStatus(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $pendingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PENDING);
        $processingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PROCESSING);
        $shippedOrder = $this->createTestOrder($customer, $address, Order::STATUS_SHIPPED);

        $pendingOrders = $this->repository->findByStatus(Order::STATUS_PENDING);
        $this->assertCount(1, $pendingOrders);
        $this->assertSame($pendingOrder, $pendingOrders[0]);

        $processingOrders = $this->repository->findByStatus(Order::STATUS_PROCESSING);
        $this->assertCount(1, $processingOrders);
        $this->assertSame($processingOrder, $processingOrders[0]);

        $shippedOrders = $this->repository->findByStatus(Order::STATUS_SHIPPED);
        $this->assertCount(1, $shippedOrders);
        $this->assertSame($shippedOrder, $shippedOrders[0]);
    }

    public function testFindByCustomer(): void
    {
        $customer1 = $this->createTestCustomer('customer1@example.com');
        $customer2 = $this->createTestCustomer('customer2@example.com');
        $address = $this->createTestAddress();

        $order1 = $this->createTestOrder($customer1, $address);
        $order2 = $this->createTestOrder($customer1, $address);
        $order3 = $this->createTestOrder($customer2, $address);

        $customer1Orders = $this->repository->findByCustomer($customer1);
        $this->assertCount(2, $customer1Orders);
        $this->assertContains($order1, $customer1Orders);
        $this->assertContains($order2, $customer1Orders);

        $customer2Orders = $this->repository->findByCustomer($customer2);
        $this->assertCount(1, $customer2Orders);
        $this->assertContains($order3, $customer2Orders);
    }

    public function testFindByDateRange(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $order1 = $this->createTestOrder($customer, $address);
        $order2 = $this->createTestOrder($customer, $address);

        $startDate = new \DateTimeImmutable('-1 day');
        $endDate = new \DateTimeImmutable('+1 day');

        $ordersInRange = $this->repository->findByDateRange($startDate, $endDate);
        $this->assertCount(2, $ordersInRange);
        $this->assertContains($order1, $ordersInRange);
        $this->assertContains($order2, $ordersInRange);

        // Test with narrow date range
        $futureStart = new \DateTimeImmutable('+2 days');
        $futureEnd = new \DateTimeImmutable('+3 days');

        $futureOrders = $this->repository->findByDateRange($futureStart, $futureEnd);
        $this->assertCount(0, $futureOrders);
    }

    public function testFindByStatuses(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $pendingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PENDING);
        $processingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PROCESSING);
        $shippedOrder = $this->createTestOrder($customer, $address, Order::STATUS_SHIPPED);
        $deliveredOrder = $this->createTestOrder($customer, $address, Order::STATUS_DELIVERED);

        $activeStatuses = [Order::STATUS_PENDING, Order::STATUS_PROCESSING];
        $activeOrders = $this->repository->findByStatuses($activeStatuses);

        $this->assertCount(2, $activeOrders);
        $this->assertContains($pendingOrder, $activeOrders);
        $this->assertContains($processingOrder, $activeOrders);
        $this->assertNotContains($shippedOrder, $activeOrders);
        $this->assertNotContains($deliveredOrder, $activeOrders);
    }

    public function testFindPendingOrders(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $pendingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PENDING);
        $processingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PROCESSING);

        $pendingOrders = $this->repository->findPendingOrders();
        $this->assertCount(1, $pendingOrders);
        $this->assertContains($pendingOrder, $pendingOrders);
        $this->assertNotContains($processingOrder, $pendingOrders);
    }

    public function testFindProcessingOrders(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $pendingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PENDING);
        $processingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PROCESSING);

        $processingOrders = $this->repository->findProcessingOrders();
        $this->assertCount(1, $processingOrders);
        $this->assertContains($processingOrder, $processingOrders);
        $this->assertNotContains($pendingOrder, $processingOrders);
    }

    public function testFindShippedOrders(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $processingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PROCESSING);
        $shippedOrder = $this->createTestOrder($customer, $address, Order::STATUS_SHIPPED);

        $shippedOrders = $this->repository->findShippedOrders();
        $this->assertCount(1, $shippedOrders);
        $this->assertContains($shippedOrder, $shippedOrders);
        $this->assertNotContains($processingOrder, $shippedOrders);
    }

    public function testSearch(): void
    {
        $customer = $this->createTestCustomer('john.doe@example.com');
        $customer->setFirstName('John')->setLastName('Doe');
        $this->entityManager->flush();

        $address = $this->createTestAddress();
        $order = $this->createTestOrder($customer, $address);

        // Search by email
        $results = $this->repository->search('john.doe@example.com');
        $this->assertCount(1, $results);
        $this->assertContains($order, $results);

        // Search by customer name
        $results = $this->repository->search('John Doe');
        $this->assertCount(1, $results);
        $this->assertContains($order, $results);

        // Search by order number
        $results = $this->repository->search($order->getOrderNumber());
        $this->assertCount(1, $results);
        $this->assertContains($order, $results);

        // Search with no results
        $results = $this->repository->search('nonexistent');
        $this->assertCount(0, $results);
    }

    public function testFindByMinimumTotal(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $lowValueOrder = $this->createTestOrder($customer, $address);
        $lowValueOrder->setTotalAmount('50.00');
        $this->entityManager->flush();

        $highValueOrder = $this->createTestOrder($customer, $address);
        $highValueOrder->setTotalAmount('200.00');
        $this->entityManager->flush();

        $highValueOrders = $this->repository->findByMinimumTotal(100.0);
        $this->assertCount(1, $highValueOrders);
        $this->assertContains($highValueOrder, $highValueOrders);
        $this->assertNotContains($lowValueOrder, $highValueOrders);
    }

    public function testFindRecentOrders(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $recentOrder = $this->createTestOrder($customer, $address);

        $recentOrders = $this->repository->findRecentOrders(30);
        $this->assertCount(1, $recentOrders);
        $this->assertContains($recentOrder, $recentOrders);

        // Test with 0 days (should return no orders)
        $recentOrders = $this->repository->findRecentOrders(0);
        $this->assertCount(0, $recentOrders);
    }

    public function testGetOrderStatistics(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $pendingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PENDING);
        $pendingOrder->setTotalAmount('100.00');

        $processingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PROCESSING);
        $processingOrder->setTotalAmount('200.00');

        $this->entityManager->flush();

        $statistics = $this->repository->getOrderStatistics();

        $this->assertCount(2, $statistics);

        $pendingStats = null;
        $processingStats = null;

        foreach ($statistics as $stat) {
            if ($stat['status'] === Order::STATUS_PENDING) {
                $pendingStats = $stat;
            } elseif ($stat['status'] === Order::STATUS_PROCESSING) {
                $processingStats = $stat;
            }
        }

        $this->assertNotNull($pendingStats);
        $this->assertEquals(1, $pendingStats['count']);
        $this->assertEquals('100.00', $pendingStats['totalAmount']);

        $this->assertNotNull($processingStats);
        $this->assertEquals(1, $processingStats['count']);
        $this->assertEquals('200.00', $processingStats['totalAmount']);
    }

    public function testFindOrdersToShip(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();

        $pendingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PENDING);
        $processingOrder = $this->createTestOrder($customer, $address, Order::STATUS_PROCESSING);
        $shippedOrder = $this->createTestOrder($customer, $address, Order::STATUS_SHIPPED);

        $ordersToShip = $this->repository->findOrdersToShip();
        $this->assertCount(1, $ordersToShip);
        $this->assertContains($processingOrder, $ordersToShip);
        $this->assertNotContains($pendingOrder, $ordersToShip);
        $this->assertNotContains($shippedOrder, $ordersToShip);
    }
}