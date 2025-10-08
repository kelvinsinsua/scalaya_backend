<?php

namespace App\Tests\Validator;

use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Validator\Constraints\SufficientStock;
use App\Validator\Constraints\SufficientStockValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SufficientStockValidatorTest extends TestCase
{
    private SufficientStockValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new SufficientStockValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        
        $this->validator->initialize($this->context);
    }

    public function testValidateWithSufficientStock(): void
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
            ->setStatus('available')
            ->setSupplier($supplier);

        $orderItem = new OrderItem();
        $orderItem->setQuantity(5)
            ->setProduct($product);

        $constraint = new SufficientStock();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($orderItem, $constraint);
    }

    public function testValidateWithInsufficientStock(): void
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
            ->setStockLevel(3)
            ->setStatus('available')
            ->setSupplier($supplier);

        $orderItem = new OrderItem();
        $orderItem->setQuantity(5)
            ->setProduct($product);

        $constraint = new SufficientStock();

        $this->violationBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($this->violationBuilder);

        $this->validator->validate($orderItem, $constraint);
    }

    public function testValidateWithUnavailableProduct(): void
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
            ->setStatus('discontinued')
            ->setSupplier($supplier);

        $orderItem = new OrderItem();
        $orderItem->setQuantity(5)
            ->setProduct($product);

        $constraint = new SufficientStock();

        $this->violationBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        
        $this->violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('order_item.product_not_available')
            ->willReturn($this->violationBuilder);

        $this->validator->validate($orderItem, $constraint);
    }

    public function testValidateWithNullProduct(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setQuantity(5);

        $constraint = new SufficientStock();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($orderItem, $constraint);
    }
}