<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SeoService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getMeta(string $type, string $slug): ?array
    {
        $cacheKey = sprintf('seo_%s_%s', $type, $slug);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($type, $slug) {
            $item->expiresAfter(86400);
            $table = match ($type) {
                'category' => 'categories',
                'product' => 'products',
                'service' => 'services',
                default => null,
            };
            if ($table === null) {
                return null;
            }

            $row = $this->connection->fetchAssociative(
                sprintf('SELECT name, description, meta_title, meta_description, slug FROM %s WHERE slug = :slug LIMIT 1', $table),
                ['slug' => $slug]
            );
            if ($row === false) {
                return null;
            }

            $title = $row['meta_title'] ?: sprintf('%s — купить в Контур-М', $row['name']);
            $description = $row['meta_description'] ?: mb_substr((string) ($row['description'] ?? ''), 0, 160);
            if ($description === '') {
                $description = mb_substr($title, 0, 160);
            }

            $pathPrefix = match ($type) {
                'category' => 'categories',
                'product' => 'products',
                'service' => 'services',
            };

            return [
                'data' => [
                    'title' => $title,
                    'description' => $description,
                    'canonical_url' => sprintf('https://merniki.ru/%s/%s', $pathPrefix, $slug),
                    'robots' => 'index, follow',
                ],
            ];
        });
    }

    public function canonical(?string $url, string $type): array
    {
        if ($url === null || $url === '') {
            return ['canonical_url' => null];
        }

        if ($type === 'category') {
            $parts = parse_url($url);
            $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'merniki.ru') . ($parts['path'] ?? '');
            return ['canonical_url' => $base];
        }

        return ['canonical_url' => $url];
    }
}

