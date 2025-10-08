<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Customer;
use App\Entity\Address;
use App\Entity\Product;
use App\Entity\Supplier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testOrderCreation(): void
    {
        $order = new Order();

        $this->assertNull($order->getId());
        $this->assertNotEmpty($order->getOrderNumber());
        $this->assertStringStartsWith('ORD-', $order->getOrderNumber());
        $this->assertEquals('0.00', $order->getSubtotal());
        $this->assertEquals('0.00', $order->getTaxAmount());
        $this->assertEquals('0.00', $order->getShippingAmount());
        $this->assertEquals('0.00', $order->getTotalAmount());
        $this->assertEquals(Order::STATUS_PENDING, $order->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getUpdatedAt());
        $this->assertCount(0, $order->getOrderItems());
    }

    public function testOrderNumberGeneration(): void
    {
        $order1 = new Order();
        $order2 = new Order();

        $this->assertNotEquals($order1->getOrderNumber(), $order2->getOrderNumber());
        $this->assertStringStartsWith('ORD-' . date('Y'), $order1->getOrderNumber());
        $this->assertStringStartsWith('ORD-' . date('Y'), $order2->getOrderNumber());
    }

    public function testStatusConstants(): void
    {
        $this->assertEquals('pending', Order::STATUS_PENDING);
        $this->assertEquals('processing', Order::STATUS_PROCESSING);
        $this->assertEquals('shipped', Order::STATUS_SHIPPED);
        $this->assertEquals('delivered', Order::STATUS_DELIVERED);
        $this->assertEquals('cancelled', Order::STATUS_CANCELLED);

        $expectedStatuses = [
            'pending',
            'processing',
            'shipped',
            'delivered',
            'cancelled'
        ];
        $this->assertEquals($expectedStatuses, Order::STATUSES);
    }

    public function testStatusMethods(): void
    {
        $order = new Order();

        $this->assertTrue($order->isPending());
        $this->assertFalse($order->isProcessing());
        $this->assertFalse($order->isShipped());
        $this->assertFalse($order->isDelivered());
        $this->assertFalse($order->isCancelled());

        $order->setStatus(Order::STATUS_PROCESSING);
        $this->assertFalse($order->isPending());
        $this->assertTrue($order->isProcessing());

        $order->setStatus(Order::STATUS_SHIPPED);
        $this->assertTrue($order->isShipped());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getShippedAt());

        $order->setStatus(Order::STATUS_DELIVERED);
        $this->assertTrue($order->isDelivered());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getDeliveredAt());

        $order->setStatus(Order::STATUS_CANCELLED);
        $this->assertTrue($order->isCancelled());
    }

    public function testCanBeCancelled(): void
    {
        $order = new Order();

        $this->assertTrue($order->canBeCancelled()); // pending

        $order->setStatus(Order::STATUS_PROCESSING);
        $this->assertTrue($order->canBeCancelled());

        $order->setStatus(Order::STATUS_SHIPPED);
        $this->assertFalse($order->canBeCancelled());

        $order->setStatus(Order::STATUS_DELIVERED);
        $this->assertFalse($order->canBeCancelled());

        $order->setStatus(Order::STATUS_CANCELLED);
        $this->assertFalse($order->canBeCancelled());
    }

    public function testCustomerRelationship(): void
    {
        $order = new Order();
        $customer = new Customer();
        $customer->setEmail('test@example.com')
                 ->setFirstName('John')
                 ->setLastName('Doe');

        $order->setCustomer($customer);

        $this->assertSame($customer, $order->getCustomer());
    }

    public function testShippingAddressRelationship(): void
    {
        $order = new Order();
        $address = new Address();
        $address->setFirstName('John')
                ->setLastName('Doe')
                ->setAddressLine1('123 Main St')
                ->setCity('Anytown')
                ->setState('CA')
                ->setPostalCode('12345')
                ->setCountry('US');

        $order->setShippingAddress($address);

        $this->assertSame($address, $order->getShippingAddress());
    }

    public function testOrderItemsRelationship(): void
    {
        $order = new Order();
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('20.00')
                ->setSupplier($supplier);

        $orderItem = new OrderItem();
        $orderItem->setProduct($product)
                  ->setQuantity(2)
                  ->setUnitPrice('20.00');

        $order->addOrderItem($orderItem);

        $this->assertCount(1, $order->getOrderItems());
        $this->assertTrue($order->getOrderItems()->contains($orderItem));
        $this->assertSame($order, $orderItem->getOrder());

        $order->removeOrderItem($orderItem);
        $this->assertCount(0, $order->getOrderItems());
        $this->assertFalse($order->getOrderItems()->contains($orderItem));
    }

    public function testCalculateTotals(): void
    {
        $order = new Order();
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product1 = new Product();
        $product1->setName('Product 1')
                 ->setSku('PROD-001')
                 ->setSupplierReference('SUP-001')
                 ->setCostPrice('10.00')
                 ->setSellingPrice('20.00')
                 ->setSupplier($supplier);

        $product2 = new Product();
        $product2->setName('Product 2')
                 ->setSku('PROD-002')
                 ->setSupplierReference('SUP-002')
                 ->setCostPrice('15.00')
                 ->setSellingPrice('30.00')
                 ->setSupplier($supplier);

        $orderItem1 = new OrderItem();
        $orderItem1->setProduct($product1)
                   ->setQuantity(2)
                   ->setUnitPrice('20.00');

        $orderItem2 = new OrderItem();
        $orderItem2->setProduct($product2)
                   ->setQuantity(1)
                   ->setUnitPrice('30.00');

        $order->addOrderItem($orderItem1);
        $order->addOrderItem($orderItem2);
        $order->setTaxAmount('7.00');
        $order->setShippingAmount('5.00');

        $order->calculateTotals();

        $this->assertEquals('70.00', $order->getSubtotal()); // (2 * 20) + (1 * 30)
        $this->assertEquals('82.00', $order->getTotalAmount()); // 70 + 7 + 5
    }

    public function testGetItemCount(): void
    {
        $order = new Order();
        $this->assertEquals(0, $order->getItemCount());

        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('20.00')
                ->setSupplier($supplier);

        $orderItem1 = new OrderItem();
        $orderItem1->setProduct($product)->setQuantity(2);

        $orderItem2 = new OrderItem();
        $orderItem2->setProduct($product)->setQuantity(3);

        $order->addOrderItem($orderItem1);
        $order->addOrderItem($orderItem2);

        $this->assertEquals(2, $order->getItemCount());
    }

    public function testGetTotalQuantity(): void
    {
        $order = new Order();
        $this->assertEquals(0, $order->getTotalQuantity());

        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('20.00')
                ->setSupplier($supplier);

        $orderItem1 = new OrderItem();
        $orderItem1->setProduct($product)->setQuantity(2);

        $orderItem2 = new OrderItem();
        $orderItem2->setProduct($product)->setQuantity(3);

        $order->addOrderItem($orderItem1);
        $order->addOrderItem($orderItem2);

        $this->assertEquals(5, $order->getTotalQuantity()); // 2 + 3
    }

    public function testValidation(): void
    {
        $order = new Order();

        // Test valid order
        $customer = new Customer();
        $customer->setEmail('test@example.com')
                 ->setFirstName('John')
                 ->setLastName('Doe');

        $address = new Address();
        $address->setFirstName('John')
                ->setLastName('Doe')
                ->setAddressLine1('123 Main St')
                ->setCity('Anytown')
                ->setState('CA')
                ->setPostalCode('12345')
                ->setCountry('US');

        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('20.00')
                ->setSupplier($supplier);

        $orderItem = new OrderItem();
        $orderItem->setProduct($product)->setQuantity(1);

        $order->setCustomer($customer)
              ->setShippingAddress($address)
              ->addOrderItem($orderItem);

        $violations = $this->validator->validate($order);
        $this->assertCount(0, $violations);

        // Test invalid status
        $order->setStatus('invalid_status');
        $violations = $this->validator->validate($order);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testToString(): void
    {
        $order = new Order();
        $this->assertEquals($order->getOrderNumber(), (string) $order);
    }
}