<?php

/** @noinspection PhpUnused */

namespace App\Validator;

use App\Service\PhoneParser;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PhoneValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (PhoneParser::isValid((string)$value)) {
            return;
        }

        /* @var Phone $constraint */
        $this->context->buildViolation((string)$constraint->message)
            ->setParameter('{{ value }}', (string)$value)
            ->addViolation();
    }
}
