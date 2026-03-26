<?php

declare(strict_types=1);

namespace App\Controller\Search;

use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class SearchAutocompleteController
{
    public function __construct(private readonly SearchService $searchService)
    {
    }

    public function __invoke(Request $request): array
    {
        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) < 2) {
            throw new BadRequestHttpException('Query parameter "q" must be at least 2 characters.');
        }

        $limit = min(max(1, (int) $request->query->get('limit', 10)), 50);

        return [
            'data' => $this->searchService->autocomplete($q, $limit),
        ];
    }
}

