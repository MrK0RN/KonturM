<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
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
                $cid = $r['category_id'];
                if (($byId[$cid]['display_mode'] ?? '') === Category::DISPLAY_MODE_SUBCATEGORIES_ONLY) {
                    continue;
                }
                $productByCategoryId[$cid][] = [
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
        $isSubcategoriesOnly = ($category['display_mode'] ?? '') === Category::DISPLAY_MODE_SUBCATEGORIES_ONLY;
        $hasProducts = ! $isSubcategoriesOnly && (int) $this->connection->fetchOne(
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
            if ($isSubcategoriesOnly) {
                $result['products'] = [];
            } else {
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

    /**
     * @param list<string> $categoryIds
     *
     * @return array<string, mixed>
     */
    private function collectFiltersFromProductCategories(array $categoryIds): array
    {
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

        foreach ($filters as $key => $values) {
            // has_verification уже список bool — не превращаем в array_keys.
            // Карты spec_key => [value => true] тоже дают reset() === true, их нужно конвертировать.
            if ($key === 'has_verification') {
                continue;
            }

            if (! is_array($values) || $values === []) {
                continue;
            }

            $filters[$key] = array_keys($values);
            sort($filters[$key], SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $filters;
    }

    /**
     * @return array{keys: list<string>, filters: array<string, mixed>}
     */
    public function discoverFiltersForCategory(string $slug, bool $aggregate): array
    {
        $rootId = $this->getCategoryIdBySlug($slug);
        if ($rootId === null) {
            return ['keys' => [], 'filters' => []];
        }

        $categoryIds = $this->getCategoryIdsBySlug($slug, $aggregate);
        if ($categoryIds === []) {
            return ['keys' => [], 'filters' => []];
        }

        $filters = $this->collectFiltersFromProductCategories($categoryIds);
        $keys = array_keys($filters);
        sort($keys, SORT_NATURAL | SORT_FLAG_CASE);

        return ['keys' => $keys, 'filters' => $filters];
    }

    /**
     * @return array{filters: array<string, mixed>, filter_labels: array<string, string>}
     */
    public function getAvailableFilters(string $slug, bool $aggregate): array
    {
        $rootId = $this->getCategoryIdBySlug($slug);
        if ($rootId === null) {
            return ['filters' => [], 'filter_labels' => [], 'filter_order' => null];
        }

        $filterConfig = $this->getFilterConfigForCategoryId($rootId);
        $configFingerprint = $filterConfig === null ? 'none' : md5(json_encode($filterConfig, JSON_UNESCAPED_UNICODE));

        $cacheKey = sprintf('filters_%s_%s_%s', $rootId, $aggregate ? 'true' : 'false', $configFingerprint);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($slug, $aggregate, $rootId, $filterConfig) {
            $item->expiresAfter(600); // 10 minutes

            $categoryIds = $this->getCategoryIdsBySlug($slug, $aggregate);
            if ($categoryIds === []) {
                return ['filters' => [], 'filter_labels' => [], 'filter_order' => null];
            }

            $filters = $this->collectFiltersFromProductCategories($categoryIds);

            [$filters, $labels] = $this->applyFilterConfigToFilters($filters, $filterConfig);

            $filterOrder = null;
            if ($filterConfig !== null && isset($filterConfig['keys']) && is_array($filterConfig['keys']) && $filterConfig['keys'] !== []) {
                $filterOrder = array_keys($filters);
            }

            return ['filters' => $filters, 'filter_labels' => $labels, 'filter_order' => $filterOrder];
        });
    }

    private function getFilterConfigForCategoryId(string $categoryId): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT filter_config FROM categories WHERE id = :id',
            ['id' => $categoryId],
        );
        if ($row === false || ! array_key_exists('filter_config', $row) || $row['filter_config'] === null) {
            return null;
        }

        $raw = $row['filter_config'];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : null;
        }

        return is_array($raw) ? $raw : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed>|null $config
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function applyFilterConfigToFilters(array $filters, ?array $config): array
    {
        $labels = [];
        if (is_array($config) && isset($config['labels']) && is_array($config['labels'])) {
            foreach ($config['labels'] as $k => $v) {
                if (is_string($k) && is_string($v)) {
                    $labels[$k] = $v;
                }
            }
        }

        if ($config === null || ! isset($config['keys']) || ! is_array($config['keys']) || $config['keys'] === []) {
            return [$filters, $labels];
        }

        $out = [];
        foreach ($config['keys'] as $k) {
            if (! is_string($k)) {
                continue;
            }
            if (array_key_exists($k, $filters)) {
                $out[$k] = $filters[$k];
            }
        }

        return [$out, $labels];
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

        if (! $aggregate) {
            $rootMode = $this->connection->fetchOne(
                'SELECT display_mode FROM categories WHERE id = :id',
                ['id' => $rootId],
            );
            if ($rootMode === Category::DISPLAY_MODE_SUBCATEGORIES_ONLY) {
                $available = $this->getAvailableFilters($slug, false);

                return [
                    'items' => [],
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => 0,
                        'total_pages' => 0,
                    ],
                    'filters' => $available['filters'],
                    'filter_labels' => $available['filter_labels'],
                    'filter_order' => $available['filter_order'],
                ];
            }
        }

        $categoryIds = $this->getCategoryIdsBySlug($slug, $aggregate);
        if ($categoryIds === []) {
            return ['items' => [], 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0], 'filters' => []];
        }

        $params = ['categoryIds' => $categoryIds];

        $where = 'p.category_id IN (:categoryIds)';

        $workFilters = $filters;
        $hasVerification = null;
        if (array_key_exists('has_verification', $workFilters)) {
            $hasVerification = (bool) $workFilters['has_verification'];
            unset($workFilters['has_verification']);
        }

        [$specWhere, $specParams] = $this->buildTechnicalSpecsFilterWhere($workFilters);
        if ($specWhere !== '') {
            $where .= $specWhere;
            $params += $specParams;
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
    p.has_verification,
    p.technical_specs
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
            $specs = $r['technical_specs'] ?? null;
            if (is_string($specs)) {
                $decoded = json_decode($specs, true);
                $specs = is_array($decoded) ? $decoded : [];
            } elseif (! is_array($specs)) {
                $specs = [];
            }
            $r['technical_specs'] = $specs;

            return $r;
        }, $rows);

        $available = $this->getAvailableFilters($slug, $aggregate);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'filters' => $available['filters'],
            'filter_labels' => $available['filter_labels'],
            'filter_order' => $available['filter_order'],
        ];
    }

    /**
     * For products listing we want to re-use the same “available filters” extraction logic,
     * but here we already have categoryIds list. We keep it uncached because extraction itself is cached in getAvailableFilters().
     */
    private function getAvailableFiltersByRootId(string $rootId, array $categoryIds): array
    {
        return $this->collectFiltersFromProductCategories($categoryIds);
    }

    /**
     * AND across keys; OR when several values are given for the same key (typical multi-checkbox).
     *
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildTechnicalSpecsFilterWhere(array $filters): array
    {
        if ($filters === []) {
            return ['', []];
        }

        $parts = [];
        $params = [];
        $i = 0;

        foreach ($filters as $key => $rawVal) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if ($rawVal === null || $rawVal === '' || $rawVal === []) {
                continue;
            }

            $values = is_array($rawVal) ? $rawVal : [$rawVal];
            $values = array_values(array_filter(
                $values,
                static fn (mixed $v): bool => $v !== null && $v !== '' && (! is_array($v))
            ));
            if ($values === []) {
                continue;
            }

            if (count($values) === 1) {
                $parts[] = 'p.technical_specs @> :ctf' . $i;
                $params['ctf' . $i] = $this->technicalSpecsContainmentFragment($key, $values[0]);
                ++$i;
            } else {
                $ors = [];
                foreach ($values as $j => $v) {
                    $pk = 'ctf' . $i . '_' . $j;
                    $ors[] = 'p.technical_specs @> :' . $pk;
                    $params[$pk] = $this->technicalSpecsContainmentFragment($key, $v);
                }
                $parts[] = '(' . implode(' OR ', $ors) . ')';
                ++$i;
            }
        }

        if ($parts === []) {
            return ['', []];
        }

        return [' AND ' . implode(' AND ', $parts), $params];
    }

    private function technicalSpecsContainmentFragment(string $key, mixed $value): string
    {
        if (is_bool($value)) {
            $json = json_encode([$key => $value], JSON_UNESCAPED_UNICODE);
        } elseif (is_int($value) || is_float($value)) {
            $json = json_encode([$key => $value], JSON_UNESCAPED_UNICODE);
        } elseif (is_string($value)) {
            $trim = trim($value);
            // В technical_specs числа часто хранятся скаляром (Объем, л: 2; погрешность, %: 0.02),
            // а из query приходят строки — раньше строился @> {key: ["2"]} и не совпадал с {key: 2}.
            $normalized = str_replace(',', '.', $trim);
            if ($trim !== '' && is_numeric($normalized)) {
                $num = (str_contains($normalized, '.') || stripos($normalized, 'e') !== false)
                    ? (float) $normalized
                    : (int) $normalized;
                $json = json_encode([$key => $num], JSON_UNESCAPED_UNICODE);
            } else {
                $json = json_encode([$key => [$value]], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $json = json_encode([$key => [(string) $value]], JSON_UNESCAPED_UNICODE);
        }

        return $json !== false ? $json : '{}';
    }
}

