<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderItemInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['product', 'service'])]
    public ?string $type = null;

    #[Assert\NotBlank]
    public ?string $id = null;

    #[Assert\NotBlank]
    public ?string $name = null;

    public ?string $article = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $quantity = null;

    public ?float $price = null;
}

