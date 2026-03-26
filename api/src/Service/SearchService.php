<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;

final class SearchService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function search(
        string $q,
        string $type,
        int $page,
        int $limit,
        array $filters = [],
        ?string $minPrice = null,
        ?string $maxPrice = null
    ): array {
        $offset = max(0, ($page - 1) * $limit);

        $result = [];
        if ($type === 'all' || $type === 'products') {
            $result['products'] = $this->searchProducts($q, $limit, $offset, $filters, $minPrice, $maxPrice);
        }
        if ($type === 'all' || $type === 'services') {
            $result['services'] = $this->searchServices($q, $limit, $offset);
        }
        if ($type === 'all' || $type === 'categories') {
            $result['categories'] = $this->searchCategories($q, $limit, $offset);
        }

        return $result;
    }

    public function autocomplete(string $q, int $limit): array
    {
        return [
            'products' => $this->searchProducts($q, $limit, 0),
            'services' => $this->searchServices($q, $limit, 0),
            'categories' => $this->searchCategories($q, $limit, 0),
        ];
    }

    private function searchProducts(string $q, int $limit, int $offset, array $filters = [], ?string $minPrice = null, ?string $maxPrice = null): array
    {
        $where = '(p.name ILIKE :q OR p.description ILIKE :q OR p.article ILIKE :q OR p.gost_number ILIKE :q)';
        $params = ['q' => '%' . $q . '%', 'limit' => $limit, 'offset' => $offset];
        $types = [];

        if ($filters !== []) {
            $where .= ' AND p.technical_specs @> :filters';
            $params['filters'] = json_encode($filters, JSON_UNESCAPED_UNICODE);
        }
        if ($minPrice !== null) {
            $where .= ' AND p.price >= :minPrice';
            $params['minPrice'] = $minPrice;
        }
        if ($maxPrice !== null) {
            $where .= ' AND p.price <= :maxPrice';
            $params['maxPrice'] = $maxPrice;
        }

        $sql = <<<SQL
SELECT
    p.id,
    p.name,
    p.slug,
    p.photo,
    p.article,
    p.price,
    similarity(COALESCE(p.name, ''), :sim_q) AS relevance
FROM products p
WHERE {$where}
ORDER BY relevance DESC, p.created_at DESC
LIMIT :limit OFFSET :offset
SQL;
        $params['sim_q'] = $q;

        return $this->connection->fetchAllAssociative($sql, $params, $types);
    }

    private function searchServices(string $q, int $limit, int $offset): array
    {
        $sql = <<<SQL
SELECT
    s.id,
    s.name,
    s.slug,
    s.price,
    s.price_type,
    similarity(COALESCE(s.name, ''), :sim_q) AS relevance
FROM services s
WHERE (s.name ILIKE :q OR s.description ILIKE :q)
ORDER BY relevance DESC, s.sort_order ASC
LIMIT :limit OFFSET :offset
SQL;

        return $this->connection->fetchAllAssociative($sql, [
            'q' => '%' . $q . '%',
            'sim_q' => $q,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    private function searchCategories(string $q, int $limit, int $offset): array
    {
        $sql = <<<SQL
SELECT
    c.id,
    c.name,
    c.slug,
    similarity(COALESCE(c.name, ''), :sim_q) AS relevance
FROM categories c
WHERE c.name ILIKE :q
ORDER BY relevance DESC, c.sort_order ASC
LIMIT :limit OFFSET :offset
SQL;

        return $this->connection->fetchAllAssociative($sql, [
            'q' => '%' . $q . '%',
            'sim_q' => $q,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }
}

