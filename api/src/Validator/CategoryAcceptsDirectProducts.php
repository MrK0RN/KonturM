<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class CategoryAcceptsDirectProducts extends Constraint
{
    public string $message = 'В категории с режимом «только подкатегории» нельзя размещать товары. Выберите категорию, где разрешены товары, или измените режим категории.';

    public function validatedBy(): string
    {
        return CategoryAcceptsDirectProductsValidator::class;
    }
}
