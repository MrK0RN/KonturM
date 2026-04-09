<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ProductQueryService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getProductBySlug(string $slug): ?array
    {
        $cacheKey = 'product_' . $slug;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($slug) {
            $item->expiresAfter(86400); // 24 hours

            $sql = <<<SQL
SELECT
    p.*,
    c.id AS category_id,
    c.name AS category_name,
    c.slug AS category_slug,
    c.display_mode AS category_display_mode
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
WHERE p.slug = :slug
LIMIT 1
SQL;

            $row = $this->connection->fetchAssociative($sql, ['slug' => $slug]);
            if ($row === false) {
                return null;
            }

            if (($row['category_display_mode'] ?? null) === Category::DISPLAY_MODE_SUBCATEGORIES_ONLY) {
                return null;
            }

            $categoryId = $row['category_id'] ?? null;
            $breadcrumbs = $categoryId ? $this->getCategoryBreadcrumbsByCategoryId($categoryId) : [];

            $seo = $this->buildSeoForProductOrService(
                name: (string) $row['name'],
                description: $row['description'] ?? null,
                metaTitle: $row['meta_title'] ?? null,
                metaDescription: $row['meta_description'] ?? null,
            );

            $canonicalUrl = sprintf('https://merniki.ru/products/%s', $slug);

            $photos = $this->fetchProductPhotos((string) $row['id']);

            return [
                // product fields (match DB column naming from PROMPT where possible)
                'id' => $row['id'],
                'category_id' => $row['category_id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'article' => $row['article'],
                'photo' => $row['photo'],
                'photo_alt' => $row['photo_alt'],
                'photos' => $photos,
                'description' => $row['description'],
                'technical_specs' => $row['technical_specs'],
                'price' => $row['price'],
                'stock_status' => $row['stock_status'],
                'manufacturing_time' => $row['manufacturing_time'],
                'gost_number' => $row['gost_number'],
                'has_verification' => (bool) $row['has_verification'],
                'drawings' => $row['drawings'],
                'documents' => $row['documents'],
                'certificates' => $row['certificates'],
                'meta_title' => $row['meta_title'],
                'meta_description' => $row['meta_description'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],

                // additional fields required by PROMPT
                'category' => [
                    'id' => $row['category_id'],
                    'name' => $row['category_name'],
                    'slug' => $row['category_slug'],
                ],
                'breadcrumbs' => $breadcrumbs,
                'canonical_url' => $canonicalUrl,
                'seo' => [
                    'title' => $seo['title'],
                    'description' => $seo['description'],
                    'robots' => 'index, follow',
                ],
            ];
        });
    }

    /**
     * @param string[] $articles list of product article numbers
     */
    public function getProductsByArticles(array $articles): array
    {
        if ($articles === []) {
            return [];
        }

        $sql = <<<SQL
SELECT
    id,
    name,
    slug,
    photo,
    article,
    price
FROM products
WHERE article IN (:articles)
ORDER BY created_at DESC
SQL;

        return $this->connection->fetchAllAssociative(
            $sql,
            ['articles' => $articles],
            ['articles' => ArrayParameterType::STRING],
        );
    }

    public function getPopularProducts(int $limit): array
    {
        $cacheKey = 'products_popular';

        // Cache computed with max limit; controller can slice if needed.
        return $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(21600); // 6 hours

            $sql = <<<SQL
SELECT
    id,
    name,
    slug,
    photo,
    article,
    price
FROM products
ORDER BY
    has_verification DESC,
    price DESC NULLS LAST,
    created_at DESC
LIMIT 20
SQL;

            return $this->connection->fetchAllAssociative($sql);
        });
    }

    public function getNewProducts(int $limit): array
    {
        $cacheKey = 'products_new';

        return $this->cache->get($cacheKey, function (ItemInterface $item) {
            $item->expiresAfter(21600); // 6 hours

            $sql = <<<SQL
SELECT
    id,
    name,
    slug,
    photo,
    article,
    price
FROM products
ORDER BY created_at DESC
LIMIT 20
SQL;

            return $this->connection->fetchAllAssociative($sql);
        });
    }

    public function getServiceBySlug(string $slug): ?array
    {
        $sql = <<<SQL
SELECT *
FROM services
WHERE slug = :slug
LIMIT 1
SQL;

        $row = $this->connection->fetchAssociative($sql, ['slug' => $slug]);
        if ($row === false) {
            return null;
        }

        $seo = $this->buildSeoForProductOrService(
            name: (string) $row['name'],
            description: $row['description'] ?? null,
            metaTitle: $row['meta_title'] ?? null,
            metaDescription: $row['meta_description'] ?? null,
        );

        $canonicalUrl = sprintf('https://merniki.ru/services/%s', $slug);

        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'price' => $row['price'],
            'price_type' => $row['price_type'],
            'photo' => $row['photo'],
            'requires_technical_spec' => (bool) $row['requires_technical_spec'],
            'meta_title' => $row['meta_title'],
            'meta_description' => $row['meta_description'],
            'sort_order' => (int) $row['sort_order'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],

            'canonical_url' => $canonicalUrl,
            'seo' => [
                'title' => $seo['title'],
                'description' => $seo['description'],
                'robots' => 'index, follow',
            ],
        ];
    }

    private function getCategoryBreadcrumbsByCategoryId(string $categoryId): array
    {
        $sql = <<<SQL
WITH RECURSIVE chain AS (
    SELECT id, parent_id, name, slug, 0 AS lvl
    FROM categories
    WHERE id = :id
    UNION ALL
    SELECT c.id, c.parent_id, c.name, c.slug, chain.lvl + 1 AS lvl
    FROM categories c
    INNER JOIN chain ON c.id = chain.parent_id
)
SELECT id, name, slug, lvl
FROM chain
ORDER BY lvl DESC
SQL;

        $rows = $this->connection->fetchAllAssociative($sql, ['id' => $categoryId]);

        return array_map(static fn (array $r) => [
            'id' => $r['id'],
            'name' => $r['name'],
            'slug' => $r['slug'],
        ], $rows);
    }

    /**
     * @return list<array{id: string, url: string, thumb_url: string, alt: ?string, sort_order: int, is_primary: bool}>
     */
    private function fetchProductPhotos(string $productId): array
    {
        $sql = <<<'SQL'
SELECT id, path, thumb_path, alt, sort_order, is_primary
FROM media_assets
WHERE owner_type = 'product' AND owner_id = :id
ORDER BY is_primary DESC, sort_order ASC, created_at ASC
SQL;

        $rows = $this->connection->fetchAllAssociative($sql, ['id' => $productId]);

        return array_map(static function (array $r): array {
            $primary = $r['is_primary'];

            return [
                'id' => (string) $r['id'],
                'url' => (string) $r['path'],
                'thumb_url' => (string) $r['thumb_path'],
                'alt' => isset($r['alt']) && $r['alt'] !== null && $r['alt'] !== '' ? (string) $r['alt'] : null,
                'sort_order' => (int) $r['sort_order'],
                'is_primary' => $primary === true || $primary === 't' || $primary === 1 || $primary === '1',
            ];
        }, $rows);
    }

    private function buildSeoForProductOrService(
        string $name,
        ?string $description,
        ?string $metaTitle,
        ?string $metaDescription,
    ): array {
        $title = $metaTitle !== null && $metaTitle !== ''
            ? $metaTitle
            : sprintf('%s — купить в Контур-М', $name);

        $descriptionText = $metaDescription !== null && $metaDescription !== ''
            ? $metaDescription
            : $description;

        $descriptionText = $descriptionText !== null ? trim($descriptionText) : '';
        if ($descriptionText === '') {
            $descriptionText = $title;
        }

        // Keep within 150-160 chars as per PROMPT
        $descriptionText = mb_substr($descriptionText, 0, 160);

        return [
            'title' => $title,
            'description' => $descriptionText,
        ];
    }
}

