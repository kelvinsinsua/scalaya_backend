<?php

namespace App\Tests\Entity;

use App\Entity\Supplier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SupplierTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidSupplier(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Company')
                 ->setContactEmail('test@example.com')
                 ->setStatus('active');

        $violations = $this->validator->validate($supplier);
        $this->assertCount(0, $violations);
    }

    public function testSupplierRequiredFields(): void
    {
        $supplier = new Supplier();
        
        $violations = $this->validator->validate($supplier);
        $this->assertGreaterThan(0, $violations->count());
        
        // Check that required fields are validated
        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getPropertyPath();
        }
        
        $this->assertContains('companyName', $violationMessages);
        $this->assertContains('contactEmail', $violationMessages);
    }

    public function testSupplierEmailValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Company')
                 ->setContactEmail('invalid-email')
                 ->setStatus('active');

        $violations = $this->validator->validate($supplier);
        $this->assertGreaterThan(0, $violations->count());
        
        $found = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'contactEmail') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Email validation should fail for invalid email');
    }

    public function testSupplierStatusValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Company')
                 ->setContactEmail('test@example.com')
                 ->setStatus('invalid-status');

        $violations = $this->validator->validate($supplier);
        $this->assertGreaterThan(0, $violations->count());
        
        $found = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'status') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Status validation should fail for invalid status');
    }

    public function testSupplierIsActive(): void
    {
        $supplier = new Supplier();
        $supplier->setStatus('active');
        $this->assertTrue($supplier->isActive());
        
        $supplier->setStatus('inactive');
        $this->assertFalse($supplier->isActive());
    }

    public function testSupplierToString(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Company');
        
        $this->assertEquals('Test Company', (string) $supplier);
    }

    public function testSupplierTimestamps(): void
    {
        $supplier = new Supplier();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $supplier->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $supplier->getUpdatedAt());
        
        // Timestamps should be set to current time (within 1 second)
        $now = new \DateTimeImmutable();
        $this->assertLessThan(1, abs($now->getTimestamp() - $supplier->getCreatedAt()->getTimestamp()));
        $this->assertLessThan(1, abs($now->getTimestamp() - $supplier->getUpdatedAt()->getTimestamp()));
    }
}