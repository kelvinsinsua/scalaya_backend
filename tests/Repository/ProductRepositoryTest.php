<?php

namespace App\Tests\Repository;

use App\Entity\Product;
use App\Entity\Supplier;
use App\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;

class ProductRepositoryTest extends TestCase
{
    public function testRepositoryMethods(): void
    {
        // This is a basic test to ensure the repository class exists and has the expected methods
        $this->assertTrue(class_exists(ProductRepository::class));
        
        $reflection = new \ReflectionClass(ProductRepository::class);
        
        // Test that required methods exist
        $this->assertTrue($reflection->hasMethod('findBySupplier'));
        $this->assertTrue($reflection->hasMethod('findByCategory'));
        $this->assertTrue($reflection->hasMethod('findAvailable'));
        $this->assertTrue($reflection->hasMethod('findByAvailability'));
        $this->assertTrue($reflection->hasMethod('findLowStock'));
        $this->assertTrue($reflection->hasMethod('searchByNameOrSku'));
        $this->assertTrue($reflection->hasMethod('findWithFilters'));
        $this->assertTrue($reflection->hasMethod('getCategories'));
        $this->assertTrue($reflection->hasMethod('countByStatus'));
        $this->assertTrue($reflection->hasMethod('findBySupplierPaginated'));
        $this->assertTrue($reflection->hasMethod('save'));
        $this->assertTrue($reflection->hasMethod('remove'));
    }

    public function testRepositoryMethodSignatures(): void
    {
        $reflection = new \ReflectionClass(ProductRepository::class);
        
        // Test findBySupplier method signature
        $method = $reflection->getMethod('findBySupplier');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('supplier', $parameters[0]->getName());
        
        // Test findByCategory method signature
        $method = $reflection->getMethod('findByCategory');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('category', $parameters[0]->getName());
        
        // Test findLowStock method signature
        $method = $reflection->getMethod('findLowStock');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('threshold', $parameters[0]->getName());
        $this->assertEquals(10, $parameters[0]->getDefaultValue());
        
        // Test findWithFilters method signature
        $method = $reflection->getMethod('findWithFilters');
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('filters', $parameters[0]->getName());
        
        // Test findBySupplierPaginated method signature
        $method = $reflection->getMethod('findBySupplierPaginated');
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);
        $this->assertEquals('supplier', $parameters[0]->getName());
        $this->assertEquals('page', $parameters[1]->getName());
        $this->assertEquals('limit', $parameters[2]->getName());
        $this->assertEquals(1, $parameters[1]->getDefaultValue());
        $this->assertEquals(20, $parameters[2]->getDefaultValue());
    }
}