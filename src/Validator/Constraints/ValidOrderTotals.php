<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidOrderTotals extends Constraint
{
    public string $message = 'order.totals.invalid';
    
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}