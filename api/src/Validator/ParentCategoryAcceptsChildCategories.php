<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ParentCategoryAcceptsChildCategories extends Constraint
{
    public string $message = 'Нельзя сделать подкатегорией категорию с режимом «только товары». Смените режим родителя или выберите другую родительскую категорию.';

    public function validatedBy(): string
    {
        return ParentCategoryAcceptsChildCategoriesValidator::class;
    }
}
