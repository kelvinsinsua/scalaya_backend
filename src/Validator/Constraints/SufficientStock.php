<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SufficientStock extends Constraint
{
    public string $message = 'order_item.insufficient_stock';
    
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}