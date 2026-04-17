<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CertificatesCatalogService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class CertificatesCatalogController
{
    public function __construct(
        private readonly CertificatesCatalogService $catalog,
    ) {
    }

    #[Route('/api/certificates-catalog', name: 'api_certificates_catalog_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse($this->catalog->getCatalog());
    }
}
