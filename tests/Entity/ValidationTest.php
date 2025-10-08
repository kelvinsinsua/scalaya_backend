<?php

namespace App\Tests\Entity;

use App\Entity\Address;
use App\Entity\AdminUser;
use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Supplier;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testAddressValidation(): void
    {
        // Test valid address
        $address = new Address();
        $address->setFirstName('John')
            ->setLastName('Doe')
            ->setAddressLine1('123 Test St')
            ->setCity('Test City')
            ->setState('Test State')
            ->setPostalCode('12345')
            ->setCountry('US');

        $violations = $this->validator->validate($address);
        $this->assertCount(0, $violations);

        // Test invalid address - missing required fields
        $invalidAddress = new Address();
        $violations = $this->validator->validate($invalidAddress);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid phone format
        $address->setPhone('invalid-phone');
        $violations = $this->validator->validate($address);
        $this->assertGreaterThan(0, $violations->count());

        // Test valid phone format
        $address->setPhone('+1234567890');
        $violations = $this->validator->validate($address);
        $this->assertCount(0, $violations);

        // Test invalid country code
        $address->setCountry('INVALID');
        $violations = $this->validator->validate($address);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testSupplierValidation(): void
    {
        // Test valid supplier
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Company')
            ->setContactEmail('test@company.com')
            ->setStatus('active');

        $violations = $this->validator->validate($supplier);
        $this->assertCount(0, $violations);

        // Test invalid email
        $supplier->setContactEmail('invalid-email');
        $violations = $this->validator->validate($supplier);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid status
        $supplier->setContactEmail('test@company.com')
            ->setStatus('invalid-status');
        $violations = $this->validator->validate($supplier);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid phone format
        $supplier->setStatus('active')
            ->setPhone('invalid-phone');
        $violations = $this->validator->validate($supplier);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testProductValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
            ->setContactEmail('test@supplier.com');

        // Test valid product
        $product = new Product();
        $product->setName('Test Product')
            ->setSku('TEST-001')
            ->setSupplierReference('SUP-001')
            ->setCostPrice('10.00')
            ->setSellingPrice('15.00')
            ->setStockLevel(10)
            ->setSupplier($supplier);

        $violations = $this->validator->validate($product);
        $this->assertCount(0, $violations);

        // Test negative prices
        $product->setCostPrice('-5.00');
        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, $violations->count());

        $product->setCostPrice('10.00')
            ->setSellingPrice('-15.00');
        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, $violations->count());

        // Test negative stock level
        $product->setSellingPrice('15.00')
            ->setStockLevel(-5);
        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid images array
        $product->setStockLevel(10)
            ->setImages(['invalid-url', 'another-invalid-url']);
        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, $violations->count());

        // Test valid images array
        $product->setImages(['https://example.com/image1.jpg', 'https://example.com/image2.jpg']);
        $violations = $this->validator->validate($product);
        $this->assertCount(0, $violations);

        // Test too many images
        $product->setImages(array_fill(0, 15, 'https://example.com/image.jpg'));
        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid dimensions
        $product->setImages([])
            ->setDimensions(['width' => -5, 'height' => 10, 'depth' => 15]);
        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, $violations->count());

        // Test valid dimensions
        $product->setDimensions(['width' => 5, 'height' => 10, 'depth' => 15]);
        $violations = $this->validator->validate($product);
        $this->assertCount(0, $violations);

        // Test extra fields in dimensions
        $product->setDimensions(['width' => 5, 'height' => 10, 'depth' => 15, 'extra' => 20]);
        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testCustomerValidation(): void
    {
        // Test valid customer
        $customer = new Customer();
        $customer->setEmail('unique-test-' . uniqid() . '@example.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $violations = $this->validator->validate($customer);
        $this->assertCount(0, $violations, 'Valid customer should have no violations: ' . (string) $violations);

        // Test invalid email
        $customer->setEmail('invalid-email');
        $violations = $this->validator->validate($customer);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid phone format
        $customer->setEmail('test@example.com')
            ->setPhone('invalid-phone');
        $violations = $this->validator->validate($customer);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid status
        $customer->setPhone(null)
            ->setStatus('invalid-status');
        $violations = $this->validator->validate($customer);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testAdminUserValidation(): void
    {
        // Test valid admin user
        $adminUser = new AdminUser();
        $adminUser->setEmail('admin-test-' . uniqid() . '@example.com')
            ->setPassword('password123')
            ->setFirstName('Admin')
            ->setLastName('User')
            ->setRoles(['ROLE_ADMIN']);

        $violations = $this->validator->validate($adminUser);
        $this->assertCount(0, $violations);

        // Test invalid email
        $adminUser->setEmail('invalid-email');
        $violations = $this->validator->validate($adminUser);
        $this->assertGreaterThan(0, $violations->count());

        // Test short password
        $adminUser->setEmail('admin@example.com')
            ->setPassword('short');
        $violations = $this->validator->validate($adminUser);
        $this->assertGreaterThan(0, $violations->count());

        // Test empty roles
        $adminUser->setPassword('password123')
            ->setRoles([]);
        $violations = $this->validator->validate($adminUser);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid status - we need to use reflection to bypass the setter validation
        $adminUser->setRoles(['ROLE_ADMIN']);
        $reflection = new \ReflectionClass($adminUser);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($adminUser, 'invalid-status');
        $violations = $this->validator->validate($adminUser);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testOrderItemValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
            ->setContactEmail('test@supplier.com');

        $product = new Product();
        $product->setName('Test Product')
            ->setSku('TEST-001')
            ->setSupplierReference('SUP-001')
            ->setCostPrice('10.00')
            ->setSellingPrice('15.00')
            ->setStockLevel(10)
            ->setSupplier($supplier);

        $customer = new Customer();
        $customer->setEmail('orderitem-test-' . uniqid() . '@example.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $address = new Address();
        $address->setFirstName('John')
            ->setLastName('Doe')
            ->setAddressLine1('123 Test St')
            ->setCity('Test City')
            ->setState('Test State')
            ->setPostalCode('12345')
            ->setCountry('US');

        $order = new Order();
        $order->setCustomer($customer)
            ->setShippingAddress($address);

        // Test valid order item
        $orderItem = new OrderItem();
        $orderItem->setQuantity(5)
            ->setUnitPrice('15.00')
            ->setProduct($product)
            ->setOrder($order);

        $violations = $this->validator->validate($orderItem);
        $this->assertCount(0, $violations, 'Valid order item should have no violations: ' . (string) $violations);

        // Test zero quantity
        $orderItem->setQuantity(0);
        $violations = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, $violations->count());

        // Test negative quantity
        $orderItem->setQuantity(-1);
        $violations = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, $violations->count());

        // Test negative unit price
        $orderItem->setQuantity(5)
            ->setUnitPrice('-10.00');
        $violations = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, $violations->count());

        // Test insufficient stock (custom validator)
        $orderItem->setUnitPrice('15.00')
            ->setQuantity(15); // More than available stock (10)
        $violations = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, $violations->count());

        // Test unavailable product (custom validator)
        $product->setStatus('discontinued');
        $orderItem->setQuantity(5);
        $violations = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testOrderValidation(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $address = new Address();
        $address->setFirstName('John')
            ->setLastName('Doe')
            ->setAddressLine1('123 Test St')
            ->setCity('Test City')
            ->setState('Test State')
            ->setPostalCode('12345')
            ->setCountry('US');

        $supplier = new Supplier();
        $supplier->setCompanyName('Test Supplier')
            ->setContactEmail('test@supplier.com');

        $product = new Product();
        $product->setName('Test Product')
            ->setSku('TEST-001')
            ->setSupplierReference('SUP-001')
            ->setCostPrice('10.00')
            ->setSellingPrice('15.00')
            ->setStockLevel(10)
            ->setSupplier($supplier);

        $orderItem = new OrderItem();
        $orderItem->setQuantity(2)
            ->setUnitPrice('15.00')
            ->setLineTotal('30.00')
            ->setProduct($product);

        // Test valid order
        $order = new Order();
        $order->setCustomer($customer)
            ->setShippingAddress($address)
            ->setSubtotal('30.00')
            ->setTaxAmount('5.00')
            ->setShippingAmount('5.00')
            ->setTotalAmount('40.00')
            ->addOrderItem($orderItem);

        $violations = $this->validator->validate($order);
        $this->assertCount(0, $violations);

        // Test negative amounts
        $order->setSubtotal('-30.00');
        $violations = $this->validator->validate($order);
        $this->assertGreaterThan(0, $violations->count());

        $order->setSubtotal('30.00')
            ->setTaxAmount('-5.00');
        $violations = $this->validator->validate($order);
        $this->assertGreaterThan(0, $violations->count());

        // Test invalid status
        $order->setTaxAmount('5.00')
            ->setStatus('invalid-status');
        $violations = $this->validator->validate($order);
        $this->assertGreaterThan(0, $violations->count());

        // Test order with mismatched totals (custom validator)
        $order->setStatus('pending')
            ->setSubtotal('50.00'); // Should be 30.00
        $violations = $this->validator->validate($order);
        $this->assertGreaterThan(0, $violations->count());

        // Test order with mismatched total amount (custom validator)
        $order->setSubtotal('30.00')
            ->setTotalAmount('100.00'); // Should be 40.00
        $violations = $this->validator->validate($order);
        $this->assertGreaterThan(0, $violations->count());

        // Test order below minimum total (custom validator)
        $order->getOrderItems()->clear();
        $smallOrderItem = new OrderItem();
        $smallOrderItem->setQuantity(1)
            ->setUnitPrice('0.50')
            ->setLineTotal('0.50')
            ->setProduct($product);
        $order->addOrderItem($smallOrderItem)
            ->setSubtotal('0.50')
            ->setTaxAmount('0.00')
            ->setShippingAmount('0.00')
            ->setTotalAmount('0.50');
        $violations = $this->validator->validate($order);
        $this->assertGreaterThan(0, $violations->count());
    }
}