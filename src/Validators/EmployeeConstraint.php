<?php


namespace App\Validators;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EmployeeConstraint extends Constraint
{
    //to add full entity validation scope
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}