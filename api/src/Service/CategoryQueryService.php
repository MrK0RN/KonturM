<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CategoryQueryService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getCategoryIdBySlug(string $slug): ?string
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id FROM categories WHERE slug = :slug LIMIT 1',
            ['slug' => $slug],
        );

        return $row['id'] ?? null;
    }

    public function getCategoryIdsBySlug(string $slug, bool $aggregate): array
    {
        $rootId = $this->getCategoryIdBySlug($slug);
        if ($rootId === null) {
            return [];
        }

        if (! $aggregate) {
            return [$rootId];
        }

        $sql = <<<SQL
WITH RECURSIVE descendants AS (
    SELECT id, parent_id
    FROM categories
    WHERE id = :rootId
    UNION ALL
    SELECT c.id, c.parent_id
    FROM categories c
    INNER JOIN descendants d ON c.parent_id = d.id
)
SELECT id FROM descendants
SQL;

        $rows = $this->connection->fetchAllAssociative($sql, ['rootId' => $rootId]);

        return array_map(static fn (array $r) => $r['id'], $rows);
    }

    /**
     * @param bool $includeProducts whether to include direct products inside nodes
     * @param int|null $maxDepth maximum depth of nesting from roots (0 = only roots)
     */
    public function getCategoryTree(bool $includeProducts, ?int $maxDepth): array
    {
        $categories = $this->connection->fetchAllAssociative(<<<SQL
SELECT
    id,
    parent_id,
    name,
    slug,
    photo,
    photo_alt,
    is_favorite_main,
    is_favorite_sidebar,
    sort_order,
    display_mode,
    aggregate_products,
    meta_title,
    meta_description
FROM categories
ORDER BY sort_order ASC
SQL);

        $byId = [];
        $childrenByParent = [];
        foreach ($categories as $row) {
            $id = $row['id'];
            $parentId = $row['parent_id'] ?? null;

            $byId[$id] = [
                'id' => $id,
                'parent_id' => $parentId,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'photo' => $row['photo'],
                'photo_alt' => $row['photo_alt'],
                'is_favorite_main' => (bool) $row['is_favorite_main'],
                'is_favorite_sidebar' => (bool) $row['is_favorite_sidebar'],
                'sort_order' => (int) $row['sort_order'],
                'display_mode' => $row['display_mode'],
                'aggregate_products' => (bool) $row['aggregate_products'],
                'meta_title' => $row['meta_title'],
                'meta_description' => $row['meta_description'],
            ];

            $childrenByParent[$parentId ?? 'null'][] = $id;
        }

        $productByCategoryId = [];
        if ($includeProducts && $byId !== []) {
            $categoryIds = array_keys($byId);
            $sql = <<<SQL
SELECT
    id,
    category_id,
    name,
    slug,
    photo,
    article,
    price,
    stock_status,
    manufacturing_time,
    has_verification
FROM products
WHERE category_id IN (:categoryIds)
SQL;

            $rows = $this->connection->fetchAllAssociative(
                $sql,
                ['categoryIds' => $categoryIds],
                ['categoryIds' => ArrayParameterType::STRING],
            );

            foreach ($rows as $r) {
                $productByCategoryId[$r['category_id']][] = [
                    'id' => $r['id'],
                    'name' => $r['name'],
                    'slug' => $r['slug'],
                    'photo' => $r['photo'],
                    'article' => $r['article'],
                    'price' => $r['price'],
                    'stock_status' => $r['stock_status'],
                    'manufacturing_time' => $r['manufacturing_time'],
                    'has_verification' => (bool) $r['has_verification'],
                ];
            }
        }

        $build = function (string $id, int $depth) use (
            &$build,
            $byId,
            $childrenByParent,
            $productByCategoryId,
            $includeProducts,
            $maxDepth
        ): array {
            $node = $byId[$id];
            $node['children'] = [];

            if ($maxDepth !== null && $depth >= $maxDepth) {
                // Stop nesting beyond maxDepth
                if ($includeProducts) {
                    $node['products'] = $productByCategoryId[$id] ?? [];
                }

                return $node;
            }

            $childIds = $childrenByParent[$id] ?? [];
            foreach ($childIds as $childId) {
                $node['children'][] = $build($childId, $depth + 1);
            }

            if ($includeProducts) {
                $node['products'] = $productByCategoryId[$id] ?? [];
            }

            return $node;
        };

        $roots = $childrenByParent['null'] ?? [];
        $tree = [];
        foreach ($roots as $rootId) {
            $tree[] = $build($rootId, 0);
        }

        return $tree;
    }

    public function getFavorites(bool $isMain): array
    {
        $sql = $isMain
            ? 'SELECT id, name, slug, photo, sort_order FROM categories WHERE is_favorite_main = true ORDER BY sort_order ASC'
            : 'SELECT id, name, slug, photo, sort_order FROM categories WHERE is_favorite_sidebar = true ORDER BY sort_order ASC';

        return $this->connection->fetchAllAssociative($sql);
    }

    public function getCategoryBySlug(string $slug, bool $includeChildren, bool $includeProducts): ?array
    {
        $categoryId = $this->getCategoryIdBySlug($slug);
        if ($categoryId === null) {
            return null;
        }

        $category = $this->connection->fetchAssociative(
            'SELECT * FROM categories WHERE id = :id LIMIT 1',
            ['id' => $categoryId],
        );
        if ($category === false) {
            return null;
        }

        // Breadcrumbs: root -> current
        $breadcrumbsSql = <<<SQL
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

        $breadcrumbsRows = $this->connection->fetchAllAssociative($breadcrumbsSql, ['id' => $categoryId]);
        $breadcrumbs = array_map(static fn (array $r) => [
            'id' => $r['id'],
            'name' => $r['name'],
            'slug' => $r['slug'],
        ], $breadcrumbsRows);

        $hasChildren = (int) $this->connection->fetchOne(
            'SELECT COUNT(1) FROM categories WHERE parent_id = :id',
            ['id' => $categoryId],
        ) > 0;
        $hasProducts = (int) $this->connection->fetchOne(
            'SELECT COUNT(1) FROM products WHERE category_id = :id',
            ['id' => $categoryId],
        ) > 0;

        $canonicalUrl = sprintf('https://merniki.ru/categories/%s', $slug);

        $result = [
            'id' => $category['id'],
            'parent_id' => $category['parent_id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'],
            'photo' => $category['photo'],
            'photo_alt' => $category['photo_alt'],
            'is_favorite_main' => (bool) $category['is_favorite_main'],
            'is_favorite_sidebar' => (bool) $category['is_favorite_sidebar'],
            'sort_order' => (int) $category['sort_order'],
            'display_mode' => $category['display_mode'],
            'aggregate_products' => (bool) $category['aggregate_products'],
            'meta_title' => $category['meta_title'],
            'meta_description' => $category['meta_description'],
            'created_at' => $category['created_at'],
            'updated_at' => $category['updated_at'],
            'has_children' => $hasChildren,
            'has_products' => $hasProducts,
            'breadcrumbs' => $breadcrumbs,
            'canonical_url' => $canonicalUrl,
        ];

        if ($includeChildren) {
            $tree = $this->getCategoryTree(includeProducts: false, maxDepth: null);
            // getCategoryTree returns full roots tree; we need subtree rooted at this node
            $result['children'] = $this->extractSubtree($tree, $categoryId);
        }

        if ($includeProducts) {
            $productRows = $this->connection->fetchAllAssociative(<<<SQL
SELECT id, name, slug, photo, article, price, stock_status, manufacturing_time, has_verification
FROM products
WHERE category_id = :id
ORDER BY created_at DESC
LIMIT 50
SQL, ['id' => $categoryId]);

            $result['products'] = array_map(static function (array $r) {
                $r['has_verification'] = (bool) $r['has_verification'];

                return $r;
            }, $productRows);
        }

        return $result;
    }

    /**
     * @param array $tree full roots array
     * @return array children subtree (direct children and deeper)
     */
    private function extractSubtree(array $tree, string $categoryId): array
    {
        foreach ($tree as $node) {
            if (($node['id'] ?? null) === $categoryId) {
                return $node['children'] ?? [];
            }

            if (! empty($node['children'])) {
                $sub = $this->extractSubtree($node['children'], $categoryId);
                if ($sub !== []) {
                    return $sub;
                }
            }
        }

        return [];
    }

    public function getAvailableFilters(string $slug, bool $aggregate): array
    {
        $rootId = $this->getCategoryIdBySlug($slug);
        if ($rootId === null) {
            return [];
        }

        $cacheKey = sprintf('filters_%s_%s', $rootId, $aggregate ? 'true' : 'false');

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($slug, $aggregate, $rootId) {
            $item->expiresAfter(600); // 10 minutes

            $categoryIds = $this->getCategoryIdsBySlug($slug, $aggregate);
            if ($categoryIds === []) {
                return [];
            }

            $filters = [];

            $arraysSql = <<<SQL
SELECT
    e.key AS spec_key,
    elem.value AS spec_value
FROM products p
JOIN LATERAL jsonb_each(p.technical_specs) e ON true
JOIN LATERAL jsonb_array_elements_text(e.value) AS elem(value) ON jsonb_typeof(e.value) = 'array'
WHERE p.category_id IN (:categoryIds)
  AND p.technical_specs IS NOT NULL
SQL;

            $rows = $this->connection->fetchAllAssociative(
                $arraysSql,
                ['categoryIds' => $categoryIds],
                ['categoryIds' => ArrayParameterType::STRING],
            );
            foreach ($rows as $r) {
                $key = $r['spec_key'];
                $value = $r['spec_value'];
                $filters[$key][$value] = true;
            }

            $scalarsSql = <<<SQL
SELECT
    e.key AS spec_key,
    CASE
        WHEN jsonb_typeof(e.value) = 'string' THEN trim(both '"' from e.value::text)
        ELSE e.value::text
    END AS spec_value
FROM products p
JOIN LATERAL jsonb_each(p.technical_specs) e ON true
WHERE p.category_id IN (:categoryIds)
  AND p.technical_specs IS NOT NULL
  AND jsonb_typeof(e.value) <> 'array'
SQL;

            $rows = $this->connection->fetchAllAssociative(
                $scalarsSql,
                ['categoryIds' => $categoryIds],
                ['categoryIds' => ArrayParameterType::STRING],
            );
            foreach ($rows as $r) {
                $key = $r['spec_key'];
                $value = $r['spec_value'];
                if ($value === null) {
                    continue;
                }

                $filters[$key][$value] = true;
            }

            $verificationRows = $this->connection->fetchAllAssociative(<<<SQL
SELECT DISTINCT has_verification
FROM products
WHERE category_id IN (:categoryIds)
SQL, ['categoryIds' => $categoryIds], ['categoryIds' => ArrayParameterType::STRING]);

            $hasVerificationValues = [];
            foreach ($verificationRows as $row) {
                $hasVerificationValues[] = (bool) $row['has_verification'];
            }
            if ($hasVerificationValues !== []) {
                $filters['has_verification'] = array_values(array_unique($hasVerificationValues));
            }

            // Convert sets to sorted arrays
            foreach ($filters as $key => $values) {
                if (is_array($values) && $values !== [] && is_bool(reset($values))) {
                    // has_verification already
                    continue;
                }

                $filters[$key] = array_keys($values);
                sort($filters[$key], SORT_NATURAL | SORT_FLAG_CASE);
            }

            return $filters;
        });
    }

    public function getProductsForCategory(
        string $slug,
        int $page,
        int $limit,
        string $sort,
        string $order,
        bool $aggregate,
        array $filters,
        ?string $minPrice,
        ?string $maxPrice,
        ?string $search
    ): array {
        $rootId = $this->getCategoryIdBySlug($slug);
        if ($rootId === null) {
            return ['items' => [], 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0], 'filters' => []];
        }

        $categoryIds = $this->getCategoryIdsBySlug($slug, $aggregate);
        if ($categoryIds === []) {
            return ['items' => [], 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0], 'filters' => []];
        }

        $sqlFilters = [];
        $params = ['categoryIds' => $categoryIds];

        $where = 'p.category_id IN (:categoryIds)';

        // technical_specs @> filters
        $hasVerification = null;
        if (array_key_exists('has_verification', $filters)) {
            $hasVerification = (bool) $filters['has_verification'];
            unset($filters['has_verification']);
        }

        if ($filters !== []) {
            $sqlFilters = $filters;
            $where .= ' AND p.technical_specs @> :specs';
            $params['specs'] = json_encode($sqlFilters, JSON_UNESCAPED_UNICODE);
        }

        if ($hasVerification !== null) {
            $where .= ' AND p.has_verification = :hasVerification';
            $params['hasVerification'] = $hasVerification;
        }

        if ($minPrice !== null) {
            $where .= ' AND p.price >= :minPrice';
            $params['minPrice'] = $minPrice;
        }

        if ($maxPrice !== null) {
            $where .= ' AND p.price <= :maxPrice';
            $params['maxPrice'] = $maxPrice;
        }

        if ($search !== null && $search !== '') {
            $where .= ' AND p.name ILIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $countSql = <<<SQL
SELECT COUNT(1) AS total
FROM products p
WHERE {$where}
SQL;

        $total = (int) $this->connection->fetchOne($countSql, $params, ['categoryIds' => ArrayParameterType::STRING]);
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 0;

        $sortColumn = match ($sort) {
            'name' => 'p.name',
            'price' => 'p.price',
            default => 'p.created_at',
        };

        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        $offset = max(0, ($page - 1) * $limit);

        $dataSql = <<<SQL
SELECT
    p.id,
    p.name,
    p.slug,
    p.photo,
    p.article,
    p.price,
    p.stock_status,
    p.manufacturing_time,
    p.has_verification
FROM products p
WHERE {$where}
ORDER BY {$sortColumn} {$order}
LIMIT :limit OFFSET :offset
SQL;

        $paramsWithPaging = $params + [
            'limit' => $limit,
            'offset' => $offset,
        ];

        $rows = $this->connection->fetchAllAssociative(
            $dataSql,
            $paramsWithPaging,
            ['categoryIds' => ArrayParameterType::STRING],
        );

        $items = array_map(static function (array $r) {
            $r['has_verification'] = (bool) $r['has_verification'];
            return $r;
        }, $rows);

        $availableFilters = $this->getAvailableFilters($slug, $aggregate);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'filters' => $availableFilters,
        ];
    }

    /**
     * For products listing we want to re-use the same “available filters” extraction logic,
     * but here we already have categoryIds list. We keep it uncached because extraction itself is cached in getAvailableFilters().
     */
    private function getAvailableFiltersByRootId(string $rootId, array $categoryIds): array
    {
        // We cannot easily re-use the public cached method with slug here because we already have categoryIds.
        // Extracting distinct technical spec values is still relatively cheap for 500-category scale.

        if ($categoryIds === []) {
            return [];
        }

        $filters = [];

        $arraysSql = <<<SQL
SELECT
    e.key AS spec_key,
    elem.value AS spec_value
FROM products p
JOIN LATERAL jsonb_each(p.technical_specs) e ON true
JOIN LATERAL jsonb_array_elements_text(e.value) AS elem(value) ON jsonb_typeof(e.value) = 'array'
WHERE p.category_id IN (:categoryIds)
  AND p.technical_specs IS NOT NULL
SQL;

        $rows = $this->connection->fetchAllAssociative(
            $arraysSql,
            ['categoryIds' => $categoryIds],
            ['categoryIds' => ArrayParameterType::STRING],
        );
        foreach ($rows as $r) {
            $filters[$r['spec_key']][$r['spec_value']] = true;
        }

        $scalarsSql = <<<SQL
SELECT
    e.key AS spec_key,
    CASE
        WHEN jsonb_typeof(e.value) = 'string' THEN trim(both '"' from e.value::text)
        ELSE e.value::text
    END AS spec_value
FROM products p
JOIN LATERAL jsonb_each(p.technical_specs) e ON true
WHERE p.category_id IN (:categoryIds)
  AND p.technical_specs IS NOT NULL
  AND jsonb_typeof(e.value) <> 'array'
SQL;

        $rows = $this->connection->fetchAllAssociative(
            $scalarsSql,
            ['categoryIds' => $categoryIds],
            ['categoryIds' => ArrayParameterType::STRING],
        );
        foreach ($rows as $r) {
            $filters[$r['spec_key']][$r['spec_value']] = true;
        }

        $verificationRows = $this->connection->fetchAllAssociative(<<<SQL
SELECT DISTINCT has_verification
FROM products
WHERE category_id IN (:categoryIds)
SQL, ['categoryIds' => $categoryIds], ['categoryIds' => ArrayParameterType::STRING]);

        $hasVerificationValues = [];
        foreach ($verificationRows as $row) {
            $hasVerificationValues[] = (bool) $row['has_verification'];
        }
        if ($hasVerificationValues !== []) {
            $filters['has_verification'] = array_values(array_unique($hasVerificationValues));
        }

        foreach ($filters as $key => $values) {
            if (is_array($values) && $values !== [] && is_bool(reset($values))) {
                continue;
            }

            if (is_array($values)) {
                $filters[$key] = array_keys($values);
                sort($filters[$key], SORT_NATURAL | SORT_FLAG_CASE);
            }
        }

        return $filters;
    }
}

