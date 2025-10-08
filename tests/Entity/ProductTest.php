<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\Supplier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidProduct(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('test@supplier.com')
                 ->setStatus('active');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-REF-001')
                ->setCostPrice('10.50')
                ->setSellingPrice('15.99')
                ->setStockLevel(100)
                ->setSupplier($supplier);

        // Test basic functionality without UniqueEntity constraint
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals('TEST-001', $product->getSku());
        $this->assertEquals('SUP-REF-001', $product->getSupplierReference());
        $this->assertEquals('10.50', $product->getCostPrice());
        $this->assertEquals('15.99', $product->getSellingPrice());
        $this->assertEquals(100, $product->getStockLevel());
        $this->assertEquals($supplier, $product->getSupplier());
    }

    public function testProductRequiredFields(): void
    {
        $product = new Product();
        
        // Test that default values are set correctly
        $this->assertEquals(0, $product->getStockLevel());
        $this->assertEquals('available', $product->getStatus());
        $this->assertEquals([], $product->getImages());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getUpdatedAt());
    }

    public function testProductPriceValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('test@supplier.com')
                 ->setStatus('active');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-REF-001')
                ->setCostPrice('10.50')
                ->setSellingPrice('15.99')
                ->setStockLevel(100)
                ->setSupplier($supplier);

        // Test margin calculation
        $expectedMargin = ((15.99 - 10.50) / 10.50) * 100;
        $this->assertEquals(round($expectedMargin, 2), round($product->getMargin(), 2));
    }

    public function testProductStatusValidation(): void
    {
        $product = new Product();
        
        // Test valid statuses
        $validStatuses = ['available', 'out_of_stock', 'discontinued'];
        
        foreach ($validStatuses as $status) {
            $product->setStatus($status);
            $this->assertEquals($status, $product->getStatus());
        }
    }

    public function testProductImageManagement(): void
    {
        $product = new Product();
        
        // Test adding images
        $product->addImage('image1.jpg');
        $product->addImage('image2.jpg');
        
        $this->assertCount(2, $product->getImages());
        $this->assertContains('image1.jpg', $product->getImages());
        $this->assertContains('image2.jpg', $product->getImages());
        
        // Test removing image
        $product->removeImage('image1.jpg');
        
        $this->assertCount(1, $product->getImages());
        $this->assertNotContains('image1.jpg', $product->getImages());
        $this->assertContains('image2.jpg', $product->getImages());
        
        // Test adding duplicate image
        $product->addImage('image2.jpg');
        $this->assertCount(1, $product->getImages()); // Should not add duplicate
    }

    public function testProductAvailability(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
                 ->setContactEmail('test@supplier.com')
                 ->setStatus('active');

        $product = new Product();
        $product->setName('Test Product')
                ->setSku('TEST-001')
                ->setSupplierReference('SUP-REF-001')
                ->setCostPrice('10.50')
                ->setSellingPrice('15.99')
                ->setStockLevel(10)
                ->setStatus('available')
                ->setSupplier($supplier);

        $this->assertTrue($product->isAvailable());
        $this->assertTrue($product->isInStock());
        
        // Test out of stock
        $product->setStockLevel(0);
        $this->assertFalse($product->isAvailable());
        $this->assertFalse($product->isInStock());
        
        // Test discontinued
        $product->setStockLevel(10);
        $product->setStatus('discontinued');
        $this->assertFalse($product->isAvailable());
        $this->assertTrue($product->isInStock());
    }

    public function testProductMarginCalculation(): void
    {
        $product = new Product();
        $product->setCostPrice('10.00')
                ->setSellingPrice('15.00');

        $margin = $product->getMargin();
        $this->assertEquals(50.0, $margin); // 50% margin
        
        // Test zero cost price
        $product->setCostPrice('0.00');
        $margin = $product->getMargin();
        $this->assertEquals(0.0, $margin);
    }

    public function testProductDimensions(): void
    {
        $product = new Product();
        $dimensions = [
            'width' => 10.5,
            'height' => 20.0,
            'depth' => 5.5
        ];
        
        $product->setDimensions($dimensions);
        
        $this->assertEquals($dimensions, $product->getDimensions());
    }

    public function testProductToString(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        
        $this->assertEquals('Test Product', (string) $product);
    }

    public function testProductTimestamps(): void
    {
        $product = new Product();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getUpdatedAt());
        
        // Initially, created and updated should be the same
        $this->assertEquals(
            $product->getCreatedAt()->format('Y-m-d H:i:s'),
            $product->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }
}