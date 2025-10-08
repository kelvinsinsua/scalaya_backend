<?php

namespace App\Tests\Repository;

use App\Entity\OrderItem;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\Customer;
use App\Entity\Address;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderItemRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OrderItemRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(OrderItem::class);

        // Clean up database
        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderItem')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Supplier')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Customer')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Address')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up database
        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderItem')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Supplier')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Customer')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Address')->execute();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function createTestSupplier(): Supplier
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        return $supplier;
    }

    private function createTestProduct(Supplier $supplier, string $name = 'Test Product', string $sku = 'TEST-001'): Product
    {
        $product = new Product();
        $product->setName($name)
                ->setSku($sku)
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('20.00')
                ->setSupplier($supplier);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function createTestCustomer(): Customer
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com')
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

    private function createTestOrder(Customer $customer, Address $address): Order
    {
        $order = new Order();
        $order->setCustomer($customer)
              ->setShippingAddress($address);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    private function createTestOrderItem(Order $order, Product $product, int $quantity = 1, string $unitPrice = '20.00'): OrderItem
    {
        $orderItem = new OrderItem();
        $orderItem->setOrder($order)
                  ->setProduct($product)
                  ->setQuantity($quantity)
                  ->setUnitPrice($unitPrice);

        $this->entityManager->persist($orderItem);
        $this->entityManager->flush();

        return $orderItem;
    }

    public function testSaveAndRemove(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order = $this->createTestOrder($customer, $address);

        $orderItem = new OrderItem();
        $orderItem->setOrder($order)
                  ->setProduct($product)
                  ->setQuantity(2)
                  ->setUnitPrice('25.00');

        // Test save
        $this->repository->save($orderItem, true);
        $this->assertNotNull($orderItem->getId());

        $foundOrderItem = $this->repository->find($orderItem->getId());
        $this->assertSame($orderItem, $foundOrderItem);

        // Test remove
        $this->repository->remove($orderItem, true);
        $removedOrderItem = $this->repository->find($orderItem->getId());
        $this->assertNull($removedOrderItem);
    }

    public function testFindByOrder(): void
    {
        $supplier = $this->createTestSupplier();
        $product1 = $this->createTestProduct($supplier, 'Product 1', 'PROD-001');
        $product2 = $this->createTestProduct($supplier, 'Product 2', 'PROD-002');
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order1 = $this->createTestOrder($customer, $address);
        $order2 = $this->createTestOrder($customer, $address);

        $orderItem1 = $this->createTestOrderItem($order1, $product1);
        $orderItem2 = $this->createTestOrderItem($order1, $product2);
        $orderItem3 = $this->createTestOrderItem($order2, $product1);

        $order1Items = $this->repository->findByOrder($order1);
        $this->assertCount(2, $order1Items);
        $this->assertContains($orderItem1, $order1Items);
        $this->assertContains($orderItem2, $order1Items);

        $order2Items = $this->repository->findByOrder($order2);
        $this->assertCount(1, $order2Items);
        $this->assertContains($orderItem3, $order2Items);
    }

    public function testFindByProduct(): void
    {
        $supplier = $this->createTestSupplier();
        $product1 = $this->createTestProduct($supplier, 'Product 1', 'PROD-001');
        $product2 = $this->createTestProduct($supplier, 'Product 2', 'PROD-002');
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order1 = $this->createTestOrder($customer, $address);
        $order2 = $this->createTestOrder($customer, $address);

        $orderItem1 = $this->createTestOrderItem($order1, $product1);
        $orderItem2 = $this->createTestOrderItem($order2, $product1);
        $orderItem3 = $this->createTestOrderItem($order1, $product2);

        $product1Items = $this->repository->findByProduct($product1);
        $this->assertCount(2, $product1Items);
        $this->assertContains($orderItem1, $product1Items);
        $this->assertContains($orderItem2, $product1Items);

        $product2Items = $this->repository->findByProduct($product2);
        $this->assertCount(1, $product2Items);
        $this->assertContains($orderItem3, $product2Items);
    }

    public function testGetTotalQuantitySoldForProduct(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order1 = $this->createTestOrder($customer, $address);
        $order2 = $this->createTestOrder($customer, $address);

        $this->createTestOrderItem($order1, $product, 3);
        $this->createTestOrderItem($order2, $product, 5);

        $totalQuantity = $this->repository->getTotalQuantitySoldForProduct($product);
        $this->assertEquals(8, $totalQuantity); // 3 + 5
    }

    public function testGetTotalRevenueForProduct(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order1 = $this->createTestOrder($customer, $address);
        $order2 = $this->createTestOrder($customer, $address);

        $this->createTestOrderItem($order1, $product, 2, '25.00'); // 2 * 25 = 50
        $this->createTestOrderItem($order2, $product, 3, '30.00'); // 3 * 30 = 90

        $totalRevenue = $this->repository->getTotalRevenueForProduct($product);
        $this->assertEquals(140.0, $totalRevenue); // 50 + 90
    }

    public function testFindBestSellingProducts(): void
    {
        $supplier = $this->createTestSupplier();
        $product1 = $this->createTestProduct($supplier, 'Product 1', 'PROD-001');
        $product2 = $this->createTestProduct($supplier, 'Product 2', 'PROD-002');
        $product3 = $this->createTestProduct($supplier, 'Product 3', 'PROD-003');
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order = $this->createTestOrder($customer, $address);

        // Product 1: 10 units sold
        $this->createTestOrderItem($order, $product1, 10, '20.00');
        
        // Product 2: 5 units sold
        $this->createTestOrderItem($order, $product2, 5, '30.00');
        
        // Product 3: 15 units sold
        $this->createTestOrderItem($order, $product3, 15, '25.00');

        $bestSelling = $this->repository->findBestSellingProducts(2);
        $this->assertCount(2, $bestSelling);

        // Should be ordered by total quantity (descending)
        $this->assertEquals($product3->getId(), $bestSelling[0]['id']);
        $this->assertEquals(15, $bestSelling[0]['totalQuantity']);
        
        $this->assertEquals($product1->getId(), $bestSelling[1]['id']);
        $this->assertEquals(10, $bestSelling[1]['totalQuantity']);
    }

    public function testFindBulkOrderItems(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order1 = $this->createTestOrder($customer, $address);
        $order2 = $this->createTestOrder($customer, $address);

        $smallOrderItem = $this->createTestOrderItem($order1, $product, 5);
        $bulkOrderItem = $this->createTestOrderItem($order2, $product, 15);

        $bulkItems = $this->repository->findBulkOrderItems(10);
        $this->assertCount(1, $bulkItems);
        $this->assertContains($bulkOrderItem, $bulkItems);
        $this->assertNotContains($smallOrderItem, $bulkItems);
    }

    public function testGetOrderItemStatistics(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order1 = $this->createTestOrder($customer, $address);
        $order2 = $this->createTestOrder($customer, $address);

        $this->createTestOrderItem($order1, $product, 2, '20.00'); // Line total: 40.00
        $this->createTestOrderItem($order2, $product, 3, '30.00'); // Line total: 90.00

        $statistics = $this->repository->getOrderItemStatistics();

        $this->assertEquals(2, $statistics['totalItems']);
        $this->assertEquals(5, $statistics['totalQuantity']); // 2 + 3
        $this->assertEquals(130.0, $statistics['totalRevenue']); // 40 + 90
        $this->assertEquals(2.5, $statistics['averageQuantity']); // (2 + 3) / 2
        $this->assertEquals(25.0, $statistics['averageUnitPrice']); // (20 + 30) / 2
        $this->assertEquals(65.0, $statistics['averageLineTotal']); // (40 + 90) / 2
    }

    public function testFindByDateRange(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order = $this->createTestOrder($customer, $address);

        $orderItem = $this->createTestOrderItem($order, $product);

        $startDate = new \DateTimeImmutable('-1 day');
        $endDate = new \DateTimeImmutable('+1 day');

        $itemsInRange = $this->repository->findByDateRange($startDate, $endDate);
        $this->assertCount(1, $itemsInRange);
        $this->assertContains($orderItem, $itemsInRange);

        // Test with future date range
        $futureStart = new \DateTimeImmutable('+2 days');
        $futureEnd = new \DateTimeImmutable('+3 days');

        $futureItems = $this->repository->findByDateRange($futureStart, $futureEnd);
        $this->assertCount(0, $futureItems);
    }

    public function testFindByPriceRange(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress();
        $order1 = $this->createTestOrder($customer, $address);
        $order2 = $this->createTestOrder($customer, $address);
        $order3 = $this->createTestOrder($customer, $address);

        $lowPriceItem = $this->createTestOrderItem($order1, $product, 1, '15.00');
        $midPriceItem = $this->createTestOrderItem($order2, $product, 1, '25.00');
        $highPriceItem = $this->createTestOrderItem($order3, $product, 1, '35.00');

        $itemsInRange = $this->repository->findByPriceRange(20.0, 30.0);
        $this->assertCount(1, $itemsInRange);
        $this->assertContains($midPriceItem, $itemsInRange);
        $this->assertNotContains($lowPriceItem, $itemsInRange);
        $this->assertNotContains($highPriceItem, $itemsInRange);
    }

    public function testGetTotalQuantitySoldForProductWithNoSales(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);

        $totalQuantity = $this->repository->getTotalQuantitySoldForProduct($product);
        $this->assertEquals(0, $totalQuantity);
    }

    public function testGetTotalRevenueForProductWithNoSales(): void
    {
        $supplier = $this->createTestSupplier();
        $product = $this->createTestProduct($supplier);

        $totalRevenue = $this->repository->getTotalRevenueForProduct($product);
        $this->assertEquals(0.0, $totalRevenue);
    }
}