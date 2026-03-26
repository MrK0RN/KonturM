<?php

declare(strict_types=1);

namespace App\Controller\Seo;

use App\Service\SeoService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class SeoCanonicalController
{
    public function __construct(private readonly SeoService $seoService)
    {
    }

    public function __invoke(Request $request): array
    {
        $url = $request->query->get('url');
        $type = (string) $request->query->get('type', 'category');

        return $this->seoService->canonical(is_string($url) ? $url : null, $type);
    }
}

