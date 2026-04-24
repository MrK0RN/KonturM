<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Каталог PDF рядом с api/ (репозиторий: …/documents). В Docker app project_dir=/app,
 * dirname даёт «/», т.е. путь /documents — его нужно смонтировать (см. docker-compose).
 * Дополнительно ищем …/api/documents для деплоев, где PDF лежат внутри api/.
 */
final class DocumentsPathService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * Абсолютный путь к каталогу documents или null, если ни один вариант не существует.
     */
    public function resolveDirectory(): ?string
    {
        $candidates = [
            dirname($this->projectDir) . '/documents',
            $this->projectDir . '/documents',
        ];
        foreach ($candidates as $dir) {
            $real = realpath($dir);
            if ($real !== false && is_dir($real)) {
                return $real;
            }
        }

        return null;
    }

    /**
     * Предпочтительный путь для создания каталога (родитель репозитория, как в исходной вёрстке).
     */
    public function preferredDirectoryForMkdir(): string
    {
        return dirname($this->projectDir) . '/documents';
    }
}
