<?php

namespace App\Tests\Validator;

use App\Entity\Address;
use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Validator\Constraints\ValidOrderTotals;
use App\Validator\Constraints\ValidOrderTotalsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ValidOrderTotalsValidatorTest extends TestCase
{
    private ValidOrderTotalsValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new ValidOrderTotalsValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        
        $this->validator->initialize($this->context);
    }

    public function testValidateWithCorrectTotals(): void
    {
        $order = $this->createValidOrder();
        $constraint = new ValidOrderTotals();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($order, $constraint);
    }

    public function testValidateWithIncorrectSubtotal(): void
    {
        $order = $this->createValidOrder();
        $order->setSubtotal('50.00'); // Should be 30.00

        $constraint = new ValidOrderTotals();

        $this->violationBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->any())
            ->method('atPath')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->exactly(2))
            ->method('addViolation');

        $this->context->expects($this->exactly(2))
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate($order, $constraint);
    }

    public function testValidateWithIncorrectTotal(): void
    {
        $order = $this->createValidOrder();
        $order->setTotalAmount('100.00'); // Should be 40.00

        $constraint = new ValidOrderTotals();

        $this->violationBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->any())
            ->method('atPath')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('order.total_amount.mismatch')
            ->willReturn($this->violationBuilder);

        $this->validator->validate($order, $constraint);
    }

    public function testValidateWithEmptyOrderItems(): void
    {
        $order = $this->createValidOrder();
        $order->getOrderItems()->clear();
        $order->setSubtotal('0.00')
            ->setTotalAmount('5.00'); // Only tax and shipping

        $constraint = new ValidOrderTotals();

        $this->violationBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->any())
            ->method('atPath')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->exactly(2))
            ->method('addViolation');

        $this->context->expects($this->exactly(2))
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate($order, $constraint);
    }

    public function testValidateWithBelowMinimumTotal(): void
    {
        $order = $this->createValidOrder();
        
        // Create a very small order
        $order->getOrderItems()->clear();
        $orderItem = new OrderItem();
        $orderItem->setQuantity(1)
            ->setUnitPrice('0.50')
            ->setLineTotal('0.50');
        $order->addOrderItem($orderItem);
        
        $order->setSubtotal('0.50')
            ->setTaxAmount('0.00')
            ->setShippingAmount('0.00')
            ->setTotalAmount('0.50');

        $constraint = new ValidOrderTotals();

        $this->violationBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->any())
            ->method('atPath')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('order.total_amount.minimum')
            ->willReturn($this->violationBuilder);

        $this->validator->validate($order, $constraint);
    }

    private function createValidOrder(): Order
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

        $orderItem1 = new OrderItem();
        $orderItem1->setQuantity(1)
            ->setUnitPrice('15.00')
            ->setLineTotal('15.00')
            ->setProduct($product);

        $orderItem2 = new OrderItem();
        $orderItem2->setQuantity(1)
            ->setUnitPrice('15.00')
            ->setLineTotal('15.00')
            ->setProduct($product);

        $order = new Order();
        $order->setCustomer($customer)
            ->setShippingAddress($address)
            ->setSubtotal('30.00')
            ->setTaxAmount('5.00')
            ->setShippingAmount('5.00')
            ->setTotalAmount('40.00')
            ->addOrderItem($orderItem1)
            ->addOrderItem($orderItem2);

        return $order;
    }
}