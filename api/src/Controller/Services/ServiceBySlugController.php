<?php

declare(strict_types=1);

namespace App\Controller\Services;

use App\Service\ProductQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final class ServiceBySlugController
{
    public function __construct(
        private readonly ProductQueryService $queryService,
    ) {
    }

    public function __invoke(string $slug, Request $request): array
    {
        $service = $this->queryService->getServiceBySlug($slug);
        if ($service === null) {
            throw new NotFoundHttpException(sprintf('Service "%s" not found.', $slug));
        }

        return $service;
    }
}

