<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class SubcategoriesOnlyWithoutProducts extends Constraint
{
    public string $message = 'Нельзя включить режим «только подкатегории», пока в этой категории есть товары. Перенесите или удалите товары.';

    public function validatedBy(): string
    {
        return SubcategoriesOnlyWithoutProductsValidator::class;
    }
}
