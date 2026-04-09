<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Category;
use Doctrine\DBAL\Connection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SubcategoriesOnlyWithoutProductsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof SubcategoriesOnlyWithoutProducts) {
            throw new UnexpectedTypeException($constraint, SubcategoriesOnlyWithoutProducts::class);
        }

        if ($value !== Category::DISPLAY_MODE_SUBCATEGORIES_ONLY) {
            return;
        }

        $object = $this->context->getObject();
        if (! $object instanceof Category) {
            return;
        }

        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(1) FROM products WHERE category_id = :id',
            ['id' => $object->getId()],
        );

        if ($count > 0) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
