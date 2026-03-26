<?php

declare(strict_types=1);

namespace App\Controller\Seo;

use App\Service\SeoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final class SeoMetaController
{
    public function __construct(private readonly SeoService $seoService)
    {
    }

    public function __invoke(string $type, string $slug, Request $request): array
    {
        if (!in_array($type, ['category', 'product', 'service'], true)) {
            throw new BadRequestHttpException('Invalid type.');
        }

        $meta = $this->seoService->getMeta($type, $slug);
        if ($meta === null) {
            throw new NotFoundHttpException('Entity not found.');
        }

        return $meta;
    }
}

