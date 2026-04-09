<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ParentCategoryAcceptsChildCategoriesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ParentCategoryAcceptsChildCategories) {
            throw new UnexpectedTypeException($constraint, ParentCategoryAcceptsChildCategories::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            return;
        }

        $parent = $this->entityManager->find(Category::class, $value);
        if ($parent === null) {
            return;
        }

        if ($parent->getDisplayMode() === Category::DISPLAY_MODE_PRODUCTS_ONLY) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
