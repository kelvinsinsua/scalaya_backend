<?php

namespace App\Tests\Entity;

use App\Entity\OrderItem;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Supplier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderItemTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testOrderItemCreation(): void
    {
        $orderItem = new OrderItem();

        $this->assertNull($orderItem->getId());
        $this->assertEquals(1, $orderItem->getQuantity());
        $this->assertEquals('0.00', $orderItem->getUnitPrice());
        $this->assertEquals('0.00', $orderItem->getLineTotal());
        $this->assertNull($orderItem->getOrder());
        $this->assertNull($orderItem->getProduct());
    }

    public function testQuantitySetterAndGetter(): void
    {
        $orderItem = new OrderItem();

        $orderItem->setQuantity(5);
        $this->assertEquals(5, $orderItem->getQuantity());
    }

    public function testUnitPriceSetterAndGetter(): void
    {
        $orderItem = new OrderItem();

        $orderItem->setUnitPrice('25.99');
        $this->assertEquals('25.99', $orderItem->getUnitPrice());
    }

    public function testLineTotalSetterAndGetter(): void
    {
        $orderItem = new OrderItem();

        $orderItem->setLineTotal('129.95');
        $this->assertEquals('129.95', $orderItem->getLineTotal());
    }

    public function testOrderRelationship(): void
    {
        $orderItem = new OrderItem();
        $order = new Order();

        $orderItem->setOrder($order);
        $this->assertSame($order, $orderItem->getOrder());
    }

    public function testProductRelationship(): void
    {
        $orderItem = new OrderItem();
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

        $orderItem->setProduct($product);
        $this->assertSame($product, $orderItem->getProduct());
    }

    public function testCalculateLineTotalOnQuantityChange(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('25.00')
                ->setSupplier($supplier);

        $orderItem = new OrderItem();
        $orderItem->setProduct($product);
        $orderItem->setQuantity(3);

        // Line total should be calculated automatically
        $this->assertEquals('75.00', $orderItem->getLineTotal()); // 3 * 25.00
    }

    public function testCalculateLineTotalOnUnitPriceChange(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setQuantity(2);
        $orderItem->setUnitPrice('15.50');

        $this->assertEquals('31.00', $orderItem->getLineTotal()); // 2 * 15.50
    }

    public function testAutomaticUnitPriceFromProduct(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('30.00')
                ->setSupplier($supplier);

        $orderItem = new OrderItem();
        $orderItem->setProduct($product);

        // Unit price should be automatically set from product's selling price
        $this->assertEquals('30.00', $orderItem->getUnitPrice());
    }

    public function testCalculateLineTotalWithProductAndQuantity(): void
    {
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
        $orderItem->setProduct($product);
        $orderItem->setQuantity(4);

        $this->assertEquals('20.00', $orderItem->getUnitPrice());
        $this->assertEquals('80.00', $orderItem->getLineTotal()); // 4 * 20.00
    }

    public function testGetProductName(): void
    {
        $orderItem = new OrderItem();
        $this->assertNull($orderItem->getProductName());

        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Amazing Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('20.00')
                ->setSupplier($supplier);

        $orderItem->setProduct($product);
        $this->assertEquals('Amazing Product', $orderItem->getProductName());
    }

    public function testGetProductSku(): void
    {
        $orderItem = new OrderItem();
        $this->assertNull($orderItem->getProductSku());

        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('AMAZING-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('20.00')
                ->setSupplier($supplier);

        $orderItem->setProduct($product);
        $this->assertEquals('AMAZING-001', $orderItem->getProductSku());
    }

    public function testGetTotalValue(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setLineTotal('99.99');

        $this->assertEquals(99.99, $orderItem->getTotalValue());
    }

    public function testValidation(): void
    {
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

        $order = new Order();

        $orderItem = new OrderItem();
        $orderItem->setProduct($product)
                  ->setOrder($order)
                  ->setQuantity(2)
                  ->setUnitPrice('20.00');

        $violations = $this->validator->validate($orderItem);
        $this->assertCount(0, $violations);

        // Test invalid quantity (zero)
        $orderItem->setQuantity(0);
        $violations = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid quantity (negative)
        $orderItem->setQuantity(-1);
        $violations = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, $violations->count());

        // Test valid quantity
        $orderItem->setQuantity(1);
        $violations = $this->validator->validate($orderItem);
        $this->assertCount(0, $violations);
    }

    public function testValidationRequiredFields(): void
    {
        $orderItem = new OrderItem();

        // Missing required fields should cause validation errors
        $violations = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, $violations->count());

        // Check for specific validation messages
        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        $this->assertContains('order_item.order.not_null', $violationMessages);
        $this->assertContains('order_item.product.not_null', $violationMessages);
    }

    public function testToString(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setQuantity(3);

        // Without product
        $this->assertEquals('Unknown Product (x3)', (string) $orderItem);

        // With product
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('supplier@example.com');

        $product = new Product();
        $product->setName('Awesome Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-001')
                ->setCostPrice('10.00')
                ->setSellingPrice('20.00')
                ->setSupplier($supplier);

        $orderItem->setProduct($product);
        $this->assertEquals('Awesome Product (x3)', (string) $orderItem);
    }

    public function testLineTotalCalculationPrecision(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setQuantity(3);
        $orderItem->setUnitPrice('10.333'); // Price with more than 2 decimal places

        // Line total should be properly formatted to 2 decimal places
        $this->assertEquals('10.333', $orderItem->getUnitPrice());
        $this->assertEquals('31.00', $orderItem->getLineTotal()); // 3 * 10.333 = 30.999, rounded to 31.00
    }

    public function testZeroQuantityHandling(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setQuantity(0);
        $orderItem->setUnitPrice('20.00');

        $this->assertEquals('0.00', $orderItem->getLineTotal());
    }
}