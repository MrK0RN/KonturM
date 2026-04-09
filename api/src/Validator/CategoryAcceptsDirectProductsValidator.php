<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class CategoryAcceptsDirectProductsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof CategoryAcceptsDirectProducts) {
            throw new UnexpectedTypeException($constraint, CategoryAcceptsDirectProducts::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            return;
        }

        $category = $this->entityManager->find(Category::class, $value);
        if ($category === null) {
            return;
        }

        if ($category->getDisplayMode() === Category::DISPLAY_MODE_SUBCATEGORIES_ONLY) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
