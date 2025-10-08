<?php

namespace App\Validator\Constraints;

use App\Entity\Order;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidOrderTotalsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidOrderTotals) {
            throw new UnexpectedTypeException($constraint, ValidOrderTotals::class);
        }

        if (!$value instanceof Order) {
            throw new UnexpectedValueException($value, Order::class);
        }

        // Calculate expected subtotal from order items
        $expectedSubtotal = 0;
        foreach ($value->getOrderItems() as $item) {
            $expectedSubtotal += (float) $item->getLineTotal();
        }

        $actualSubtotal = (float) $value->getSubtotal();
        $taxAmount = (float) $value->getTaxAmount();
        $shippingAmount = (float) $value->getShippingAmount();
        $totalAmount = (float) $value->getTotalAmount();

        // Validate subtotal matches sum of line totals
        if (abs($expectedSubtotal - $actualSubtotal) > 0.01) {
            $this->context->buildViolation('order.subtotal.mismatch')
                ->setParameter('{{ expected }}', number_format($expectedSubtotal, 2))
                ->setParameter('{{ actual }}', number_format($actualSubtotal, 2))
                ->atPath('subtotal')
                ->addViolation();
        }

        // Validate total amount calculation
        $expectedTotal = $actualSubtotal + $taxAmount + $shippingAmount;
        if (abs($expectedTotal - $totalAmount) > 0.01) {
            $this->context->buildViolation('order.total_amount.mismatch')
                ->setParameter('{{ expected }}', number_format($expectedTotal, 2))
                ->setParameter('{{ actual }}', number_format($totalAmount, 2))
                ->atPath('totalAmount')
                ->addViolation();
        }

        // Validate order has at least one item
        if ($value->getOrderItems()->isEmpty()) {
            $this->context->buildViolation('order.order_items.empty')
                ->atPath('orderItems')
                ->addViolation();
        }

        // Validate minimum order amount (business rule)
        if ($totalAmount > 0 && $totalAmount < 1.00) {
            $this->context->buildViolation('order.total_amount.minimum')
                ->setParameter('{{ minimum }}', '1.00')
                ->atPath('totalAmount')
                ->addViolation();
        }
    }
}