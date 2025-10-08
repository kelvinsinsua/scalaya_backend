<?php

namespace App\Tests\Entity;

use App\Entity\Address;
use App\Entity\Customer;
use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCustomer(): void
    {
        $customer = new Customer();
        $customer->setEmail('john.doe@example.com')
                 ->setFirstName('John')
                 ->setLastName('Doe')
                 ->setPhone('+1234567890')
                 ->setStatus(Customer::STATUS_ACTIVE);

        // Test basic validation without UniqueEntity constraint
        $violations = $this->validator->validate($customer, null, ['Default']);
        
        // Filter out UniqueEntity violations for this test
        $filteredViolations = [];
        foreach ($violations as $violation) {
            if (strpos($violation->getMessage(), 'unique') === false) {
                $filteredViolations[] = $violation;
            }
        }
        
        $this->assertCount(0, $filteredViolations);
    }

    public function testCustomerRequiredFields(): void
    {
        $customer = new Customer();
        
        $violations = $this->validator->validate($customer);
        
        // Should have violations for email, firstName, lastName, and status
        $this->assertGreaterThan(0, $violations->count());
        
        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getPropertyPath();
        }
        
        $this->assertContains('email', $violationMessages);
        $this->assertContains('firstName', $violationMessages);
        $this->assertContains('lastName', $violationMessages);
    }

    public function testInvalidEmail(): void
    {
        $customer = new Customer();
        $customer->setEmail('invalid-email')
                 ->setFirstName('John')
                 ->setLastName('Doe')
                 ->setStatus(Customer::STATUS_ACTIVE);

        $violations = $this->validator->validate($customer, null, ['Default']);
        
        $emailViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'email' && strpos($violation->getMessage(), 'unique') === false) {
                $emailViolations[] = $violation->getMessage();
            }
        }
        
        $this->assertNotEmpty($emailViolations);
    }

    public function testInvalidStatus(): void
    {
        $customer = new Customer();
        $customer->setEmail('john.doe@example.com')
                 ->setFirstName('John')
                 ->setLastName('Doe')
                 ->setStatus('invalid_status');

        $violations = $this->validator->validate($customer, null, ['Default']);
        
        $statusViolations = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'status') {
                $statusViolations[] = $violation->getMessage();
            }
        }
        
        $this->assertNotEmpty($statusViolations);
    }

    public function testValidStatuses(): void
    {
        $validStatuses = [
            Customer::STATUS_ACTIVE,
            Customer::STATUS_INACTIVE,
            Customer::STATUS_BLOCKED
        ];

        foreach ($validStatuses as $status) {
            $customer = new Customer();
            $customer->setEmail('john.doe@example.com')
                     ->setFirstName('John')
                     ->setLastName('Doe')
                     ->setStatus($status);

            $violations = $this->validator->validate($customer, null, ['Default']);
            
            $statusViolations = [];
            foreach ($violations as $violation) {
                if ($violation->getPropertyPath() === 'status') {
                    $statusViolations[] = $violation->getMessage();
                }
            }
            
            $this->assertEmpty($statusViolations, "Status '$status' should be valid");
        }
    }

    public function testStringLengthValidation(): void
    {
        $customer = new Customer();
        $customer->setEmail(str_repeat('a', 181) . '@example.com') // Too long
                 ->setFirstName(str_repeat('a', 101)) // Too long
                 ->setLastName(str_repeat('b', 101)) // Too long
                 ->setPhone(str_repeat('1', 21)) // Too long
                 ->setStatus(Customer::STATUS_ACTIVE);

        $violations = $this->validator->validate($customer, null, ['Default']);
        
        $this->assertGreaterThan(0, $violations->count());
        
        $violationPaths = [];
        foreach ($violations as $violation) {
            if (strpos($violation->getMessage(), 'unique') === false) {
                $violationPaths[] = $violation->getPropertyPath();
            }
        }
        
        $this->assertContains('email', $violationPaths);
        $this->assertContains('firstName', $violationPaths);
        $this->assertContains('lastName', $violationPaths);
        $this->assertContains('phone', $violationPaths);
    }

    public function testBillingAddressRelationship(): void
    {
        $customer = new Customer();
        $address = new Address();
        
        $customer->setBillingAddress($address);
        
        $this->assertSame($address, $customer->getBillingAddress());
    }

    public function testShippingAddressRelationship(): void
    {
        $customer = new Customer();
        $address = new Address();
        
        $customer->setShippingAddress($address);
        
        $this->assertSame($address, $customer->getShippingAddress());
    }

    public function testOrdersRelationship(): void
    {
        $customer = new Customer();
        $order1 = new Order();
        $order2 = new Order();
        
        $this->assertCount(0, $customer->getOrders());
        
        $customer->addOrder($order1);
        $this->assertCount(1, $customer->getOrders());
        $this->assertTrue($customer->getOrders()->contains($order1));
        $this->assertSame($customer, $order1->getCustomer());
        
        $customer->addOrder($order2);
        $this->assertCount(2, $customer->getOrders());
        
        // Adding the same order again should not duplicate
        $customer->addOrder($order1);
        $this->assertCount(2, $customer->getOrders());
        
        $customer->removeOrder($order1);
        $this->assertCount(1, $customer->getOrders());
        $this->assertFalse($customer->getOrders()->contains($order1));
    }

    public function testGetFullName(): void
    {
        $customer = new Customer();
        $customer->setFirstName('John')
                 ->setLastName('Doe');
        
        $this->assertEquals('John Doe', $customer->getFullName());
        
        // Test with extra spaces
        $customer->setFirstName(' John ')
                 ->setLastName(' Doe ');
        
        $this->assertEquals('John   Doe', $customer->getFullName());
    }

    public function testIsActive(): void
    {
        $customer = new Customer();
        
        $customer->setStatus(Customer::STATUS_ACTIVE);
        $this->assertTrue($customer->isActive());
        
        $customer->setStatus(Customer::STATUS_INACTIVE);
        $this->assertFalse($customer->isActive());
        
        $customer->setStatus(Customer::STATUS_BLOCKED);
        $this->assertFalse($customer->isActive());
    }

    public function testIsBlocked(): void
    {
        $customer = new Customer();
        
        $customer->setStatus(Customer::STATUS_BLOCKED);
        $this->assertTrue($customer->isBlocked());
        
        $customer->setStatus(Customer::STATUS_ACTIVE);
        $this->assertFalse($customer->isBlocked());
        
        $customer->setStatus(Customer::STATUS_INACTIVE);
        $this->assertFalse($customer->isBlocked());
    }

    public function testToString(): void
    {
        $customer = new Customer();
        $customer->setFirstName('John')
                 ->setLastName('Doe')
                 ->setEmail('john.doe@example.com');
        
        $expected = 'John Doe (john.doe@example.com)';
        $this->assertEquals($expected, (string) $customer);
    }

    public function testDefaultValues(): void
    {
        $customer = new Customer();
        
        $this->assertEquals(Customer::STATUS_ACTIVE, $customer->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $customer->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $customer->getUpdatedAt());
        $this->assertCount(0, $customer->getOrders());
    }

    public function testStatusConstants(): void
    {
        $this->assertEquals('active', Customer::STATUS_ACTIVE);
        $this->assertEquals('inactive', Customer::STATUS_INACTIVE);
        $this->assertEquals('blocked', Customer::STATUS_BLOCKED);
        
        $expectedStatuses = ['active', 'inactive', 'blocked'];
        $this->assertEquals($expectedStatuses, Customer::STATUSES);
    }

    public function testOptionalFields(): void
    {
        $customer = new Customer();
        $customer->setEmail('john.doe@example.com')
                 ->setFirstName('John')
                 ->setLastName('Doe')
                 ->setStatus(Customer::STATUS_ACTIVE);
        // Not setting phone, billingAddress, shippingAddress
        
        $violations = $this->validator->validate($customer, null, ['Default']);
        
        // Filter out UniqueEntity violations
        $filteredViolations = [];
        foreach ($violations as $violation) {
            if (strpos($violation->getMessage(), 'unique') === false) {
                $filteredViolations[] = $violation;
            }
        }
        
        $this->assertCount(0, $filteredViolations);
        
        $this->assertNull($customer->getPhone());
        $this->assertNull($customer->getBillingAddress());
        $this->assertNull($customer->getShippingAddress());
    }
}