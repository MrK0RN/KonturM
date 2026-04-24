<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Разделы и PDF на странице «Сертификаты»: JSON в var/certificates_catalog.json.
 * Сами файлы лежат в documents/ рядом с api/ (как DocumentsController).
 */
final class CertificatesCatalogService
{
    private const MAX_UPLOAD_BYTES = 30 * 1024 * 1024;

    /**
     * Совпадает с прежним захардкоженным каталогом в design/certificates.php.
     *
     * @var array{groups: list<array{id: string, title: string, items: list<array{id: string, filename: string, label: string|null}>}>}
     */
    private const DEFAULT_CATALOG = [
        'groups' => [
            [
                'id' => 'g-default-1',
                'title' => 'Сертификаты об утверждении типа средств измерений',
                'items' => [
                    ['id' => 'i-default-1', 'filename' => 'Сертификат_ОТ_Мерники_М1Р.pdf', 'label' => null],
                    ['id' => 'i-default-2', 'filename' => 'Сертификат_ОТ_Мерники_М2Р.pdf', 'label' => null],
                    ['id' => 'i-default-3', 'filename' => 'Сертификат_ОТ_Метроштоки_МШС.pdf', 'label' => null],
                    ['id' => 'i-default-4', 'filename' => 'Сертификат_ОТ_Технические_Мерники.pdf', 'label' => null],
                    ['id' => 'i-default-5', 'filename' => 'Сертификат_ОТ_Рулетки_Р.pdf', 'label' => null],
                ],
            ],
            [
                'id' => 'g-default-2',
                'title' => 'Пасты-индикаторы',
                'items' => [
                    ['id' => 'i-default-6', 'filename' => 'Сертификат_Водочувствительная_Паста.pdf', 'label' => null],
                    ['id' => 'i-default-7', 'filename' => 'Титульный_Лист_ТУ_Паста_Акватест.pdf', 'label' => null],
                ],
            ],
        ],
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly DocumentsPathService $documentsPath,
    ) {
    }

    private function catalogFilePath(): string
    {
        return $this->projectDir . '/var/certificates_catalog.json';
    }

    private function documentsDir(): ?string
    {
        return $this->documentsPath->resolveDirectory();
    }

    /**
     * @return list<string> имена *.pdf в каталоге documents/
     */
    public function listDocumentPdfNames(): array
    {
        $base = $this->documentsDir();
        if ($base === null) {
            return [];
        }

        $names = [];
        $dh = opendir($base);
        if ($dh === false) {
            return [];
        }
        while (($entry = readdir($dh)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $base . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($full)) {
                continue;
            }
            if (strtolower((string) pathinfo($entry, PATHINFO_EXTENSION)) !== 'pdf') {
                continue;
            }
            $names[] = $entry;
        }
        closedir($dh);
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * @return array{groups: list<array<string, mixed>>}
     */
    public function getCatalog(): array
    {
        return $this->mergeFromFile();
    }

    /**
     * @param array<string, mixed> $input
     * @return array{groups: list<array<string, mixed>>}
     */
    public function save(array $input): array
    {
        $normalized = $this->validateAndNormalize($input);
        $path = $this->catalogFilePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Не удалось создать каталог: ' . $dir);
            }
        }
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('Ошибка кодирования JSON.');
        }
        if (file_put_contents($path, $json . "\n") === false) {
            throw new \RuntimeException('Не удалось записать файл: ' . $path);
        }

        return $normalized;
    }

    /**
     * @return array{filename: string, bytes: int}
     */
    public function saveUploadedPdf(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new BadRequestHttpException($file->getErrorMessage());
        }
        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            throw new BadRequestHttpException('Файл слишком большой (макс. 30 МБ).');
        }
        $ext = strtolower((string) pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new BadRequestHttpException('Нужен файл с расширением .pdf');
        }

        $base = $this->documentsDir();
        if ($base === null) {
            $preferred = $this->documentsPath->preferredDirectoryForMkdir();
            if (!@mkdir($preferred, 0775, true) && !is_dir($preferred)) {
                throw new \RuntimeException('Не удалось создать каталог documents рядом с api.');
            }
            $base = $this->documentsDir();
            if ($base === null) {
                throw new \RuntimeException('Каталог documents недоступен.');
            }
        }

        $filename = $this->uniqueSafeFilename($base, $file->getClientOriginalName());
        try {
            $file->move($base, $filename);
        } catch (\Throwable $e) {
            throw new BadRequestHttpException('Не удалось сохранить файл: ' . $e->getMessage());
        }

        $target = $base . DIRECTORY_SEPARATOR . $filename;
        $bytes = is_readable($target) ? (int) filesize($target) : 0;

        return ['filename' => $filename, 'bytes' => $bytes];
    }

    /**
     * @return array{groups: list<array<string, mixed>>}
     */
    private function mergeFromFile(): array
    {
        $path = $this->catalogFilePath();
        if (!is_readable($path)) {
            return self::DEFAULT_CATALOG;
        }
        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return self::DEFAULT_CATALOG;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return self::DEFAULT_CATALOG;
        }

        try {
            return $this->validateAndNormalize($data);
        } catch (\Throwable) {
            return self::DEFAULT_CATALOG;
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{groups: list<array<string, mixed>>}
     */
    private function validateAndNormalize(array $input): array
    {
        if (!isset($input['groups']) || !is_array($input['groups'])) {
            throw new BadRequestHttpException('Ожидается поле groups (массив).');
        }

        $groups = [];
        foreach ($input['groups'] as $idx => $g) {
            if (!is_array($g)) {
                throw new BadRequestHttpException('Элемент groups[' . $idx . '] должен быть объектом.');
            }
            $gid = isset($g['id']) && is_string($g['id']) ? trim($g['id']) : '';
            if ($gid === '') {
                throw new BadRequestHttpException('У каждого раздела должен быть непустой id.');
            }
            $title = isset($g['title']) && is_string($g['title']) ? $g['title'] : '';
            if (!isset($g['items']) || !is_array($g['items'])) {
                throw new BadRequestHttpException('Раздел «' . $gid . '»: нужен массив items.');
            }
            $items = [];
            foreach ($g['items'] as $j => $it) {
                if (!is_array($it)) {
                    throw new BadRequestHttpException('items[' . $j . '] в разделе «' . $gid . '» должен быть объектом.');
                }
                $iid = isset($it['id']) && is_string($it['id']) ? trim($it['id']) : '';
                if ($iid === '') {
                    throw new BadRequestHttpException('У каждого документа должен быть непустой id (раздел «' . $gid . '»).');
                }
                $fn = isset($it['filename']) && is_string($it['filename']) ? trim($it['filename']) : '';
                if ($fn === '' || str_contains($fn, '..') || str_contains($fn, '/') || str_contains($fn, '\\')) {
                    throw new BadRequestHttpException('Некорректное имя файла для документа «' . $iid . '».');
                }
                if (strtolower((string) pathinfo($fn, PATHINFO_EXTENSION)) !== 'pdf') {
                    throw new BadRequestHttpException('Допустимы только PDF: «' . $fn . '».');
                }
                $label = null;
                if (array_key_exists('label', $it)) {
                    if ($it['label'] === null) {
                        $label = null;
                    } elseif (is_string($it['label'])) {
                        $t = trim($it['label']);
                        $label = $t === '' ? null : $t;
                    } else {
                        throw new BadRequestHttpException('Поле label должно быть строкой или null.');
                    }
                }
                $items[] = [
                    'id' => $iid,
                    'filename' => $fn,
                    'label' => $label,
                ];
            }
            $groups[] = [
                'id' => $gid,
                'title' => $title,
                'items' => $items,
            ];
        }

        return ['groups' => $groups];
    }

    private function uniqueSafeFilename(string $dir, string $originalName): string
    {
        $base = basename(str_replace('\\', '/', $originalName));
        $base = preg_replace('/[^\p{L}\p{N}._\-\s()]/u', '_', $base) ?? $base;
        if ($base === '' || $base === '.') {
            $base = 'document.pdf';
        }
        if (!str_ends_with(strtolower($base), '.pdf')) {
            $base .= '.pdf';
        }

        $candidate = $base;
        $n = 0;
        while (is_file($dir . DIRECTORY_SEPARATOR . $candidate)) {
            ++$n;
            $stem = (string) pathinfo($base, PATHINFO_FILENAME);
            $candidate = $stem . '-' . $n . '.pdf';
        }

        return $candidate;
    }
}
