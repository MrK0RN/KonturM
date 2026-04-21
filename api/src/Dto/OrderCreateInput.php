<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderCreateInput
{
    #[Assert\NotBlank]
    public ?string $customer_name = null;

    public ?string $customer_company = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^(\+?[0-9\-\s\(\)]{7,20})$/u')]
    public ?string $customer_phone = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $customer_email = null;

    /** Пусто или ровно 10/12 цифр (юр./физ. лицо РФ). */
    #[Assert\Regex(pattern: '/^(?:\d{10}|\d{12})?$/')]
    public ?string $customer_inn = null;

    #[Assert\NotNull]
    #[Assert\Count(min: 1)]
    #[Assert\Valid]
    public array $items = [];

    public ?string $comment = null;

    public ?array $attachments = null;
}

