<?php

declare(strict_types=1);

namespace App\Controller\Products;

use App\Service\ProductQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class ProductsByArticlesController
{
    public function __construct(
        private readonly ProductQueryService $queryService,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $articlesParam = $request->query->get('articles');
        if (! is_string($articlesParam) || trim($articlesParam) === '') {
            throw new BadRequestHttpException('Query parameter "articles" is required.');
        }

        $articles = array_values(array_filter(array_map(static function (string $a) {
            return trim($a);
        }, explode(',', $articlesParam)), static fn (string $a) => $a !== ''));

        if ($articles === []) {
            throw new BadRequestHttpException('Query parameter "articles" must contain at least one article.');
        }

        return $this->queryService->getProductsByArticles($articles);
    }
}

