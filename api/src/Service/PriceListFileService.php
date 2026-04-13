<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Прайс-лист для скачивания: загруженный файл в var/price_list/; иначе — resources/default/price-list.xlsx.
 */
final class PriceListFileService
{
    private const ALLOWED_EXT = ['xlsx', 'pdf'];

    private const MAX_BYTES = 30 * 1024 * 1024;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    private function storageDir(): string
    {
        return $this->projectDir . '/var/price_list';
    }

    private function metaPath(): string
    {
        return $this->storageDir() . '/meta.json';
    }

    private function dataPath(): string
    {
        return $this->storageDir() . '/file';
    }

    private function defaultXlsxPath(): string
    {
        return $this->projectDir . '/resources/default/price-list.xlsx';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMeta(): ?array
    {
        $path = $this->metaPath();
        if (!is_readable($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Абсолютный путь к файлу для выдачи по расширению URL, или null (404).
     */
    public function resolveDownloadPath(string $ext): ?string
    {
        $ext = strtolower($ext);
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return null;
        }
        $meta = $this->getMeta();
        if ($meta !== null && ($meta['extension'] ?? '') === $ext) {
            $p = $this->dataPath();
            if (is_readable($p)) {
                return $p;
            }
        }
        if ($ext === 'xlsx') {
            $def = $this->defaultXlsxPath();
            if (is_readable($def)) {
                return $def;
            }
        }

        return null;
    }

    public function getContentType(string $ext): string
    {
        return match (strtolower($ext)) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdminInfo(): array
    {
        $meta = $this->getMeta();
        $dataPath = $this->dataPath();
        $hasCustom = $meta !== null && is_readable($dataPath);

        $defaultPath = $this->defaultXlsxPath();
        $defaultSize = is_readable($defaultPath) ? filesize($defaultPath) : false;

        if ($hasCustom) {
            $bytes = filesize($dataPath);
            $ext = is_string($meta['extension'] ?? null) ? $meta['extension'] : '';

            return [
                'has_custom' => true,
                'extension' => $ext,
                'original_filename' => is_string($meta['original_filename'] ?? null) ? $meta['original_filename'] : null,
                'bytes' => $bytes !== false ? $bytes : null,
                'updated_at' => is_string($meta['updated_at'] ?? null) ? $meta['updated_at'] : null,
                'download_path' => '/price-list.' . $ext,
                'default_xlsx_bytes' => $defaultSize !== false ? $defaultSize : null,
            ];
        }

        return [
            'has_custom' => false,
            'extension' => 'xlsx',
            'original_filename' => null,
            'bytes' => $defaultSize !== false ? $defaultSize : null,
            'updated_at' => null,
            'download_path' => '/price-list.xlsx',
            'default_xlsx_bytes' => $defaultSize !== false ? $defaultSize : null,
        ];
    }

    public function save(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new BadRequestHttpException('Файл не был загружен или повреждён.');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new BadRequestHttpException('Файл слишком большой (максимум 30 МБ).');
        }

        $origName = $file->getClientOriginalName();
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new BadRequestHttpException('Допустимы только файлы .xlsx или .pdf.');
        }

        $dir = $this->storageDir();
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Не удалось создать каталог: ' . $dir);
            }
        }

        $tmpTarget = $this->dataPath() . '.new';
        try {
            $file->move(dirname($tmpTarget), basename($tmpTarget));
        } catch (\Throwable $e) {
            throw new BadRequestHttpException('Не удалось сохранить файл: ' . $e->getMessage());
        }

        if (!is_readable($tmpTarget)) {
            throw new \RuntimeException('Временный файл недоступен после move().');
        }

        $final = $this->dataPath();
        if (is_file($final)) {
            @unlink($final);
        }
        if (!@rename($tmpTarget, $final)) {
            @unlink($tmpTarget);
            throw new \RuntimeException('Не удалось зафиксировать файл прайс-листа.');
        }

        $meta = [
            'extension' => $ext,
            'original_filename' => $origName,
            'updated_at' => gmdate('c'),
        ];
        $json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false || file_put_contents($this->metaPath(), $json . "\n") === false) {
            throw new \RuntimeException('Не удалось записать meta.json.');
        }

        return $this->getAdminInfo();
    }

    public function clear(): void
    {
        $p = $this->dataPath();
        if (is_file($p)) {
            @unlink($p);
        }
        if (is_file($this->metaPath())) {
            @unlink($this->metaPath());
        }
    }
}
