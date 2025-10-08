<?php

namespace App\Validator\Constraints;

use App\Entity\OrderItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SufficientStockValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SufficientStock) {
            throw new UnexpectedTypeException($constraint, SufficientStock::class);
        }

        if (!$value instanceof OrderItem) {
            throw new UnexpectedValueException($value, OrderItem::class);
        }

        $product = $value->getProduct();
        if (!$product) {
            return; // Let other validators handle null product
        }

        // Check if product has sufficient stock
        if ($product->getStockLevel() < $value->getQuantity()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ requested }}', (string) $value->getQuantity())
                ->setParameter('{{ available }}', (string) $product->getStockLevel())
                ->setParameter('{{ product }}', $product->getName())
                ->addViolation();
        }

        // Check if product is available
        if (!$product->isAvailable()) {
            $this->context->buildViolation('order_item.product_not_available')
                ->setParameter('{{ product }}', $product->getName())
                ->setParameter('{{ status }}', $product->getStatus())
                ->addViolation();
        }
    }
}