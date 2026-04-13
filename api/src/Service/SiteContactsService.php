<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Контакты витрины: JSON в var/site_contacts.json (рядом с api).
 */
final class SiteContactsService
{
    /** @var array<string, string> */
    public const DEFAULTS = [
        'phone_main_href' => 'tel:+78432023170',
        'phone_main_label' => '+7 (843) 202-31-70',
        'phone_extra_href' => 'tel:+79272495218',
        'phone_extra_label' => '+7 927-249-52-18',
        'email_sales' => 'kontur_m16@mail.ru',
        'email_metrology' => 'kontur_metrolog@mail.ru',
        'messenger_vk' => 'https://vk.com/konturm',
        'messenger_telegram' => 'https://t.me/konturm',
        'messenger_whatsapp' => 'https://wa.me/79785654997',
        'messenger_max' => 'https://max.ru/konturm',
        'map_iframe_src' => 'https://yandex.ru/map-widget/v1/?oid=92963604301&ll=49.275773%2C55.911633&z=17',
        'map_yandex_link' => 'https://yandex.ru/maps/org/kontur_m/92963604301/?ll=49.275773%2C55.911633&z=17',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    private function filePath(): string
    {
        return $this->projectDir . '/var/site_contacts.json';
    }

    /**
     * @return array<string, string>
     */
    public function getContacts(): array
    {
        return $this->mergeFromFile();
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function save(array $input): array
    {
        $normalized = $this->validateAndNormalize($input);
        $path = $this->filePath();
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
     * @return array<string, string>
     */
    private function mergeFromFile(): array
    {
        $base = self::DEFAULTS;
        $path = $this->filePath();
        if (!is_readable($path)) {
            return $base;
        }
        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return $base;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $base;
        }
        foreach ($base as $key => $default) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $v = $data[$key];
            if (!is_string($v)) {
                continue;
            }
            $base[$key] = $v;
        }

        return $base;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validateAndNormalize(array $input): array
    {
        $out = self::DEFAULTS;
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $v = $input[$key];
            if (!is_string($v)) {
                throw new BadRequestHttpException('Поле ' . $key . ' должно быть строкой.');
            }
            $v = trim($v);
            if ($v === '') {
                throw new BadRequestHttpException('Поле ' . $key . ' не может быть пустым.');
            }
            $this->assertField($key, $v);
            $out[$key] = $v;
        }

        return $out;
    }

    private function assertField(string $key, string $v): void
    {
        if (str_starts_with($key, 'phone_') && str_ends_with($key, '_href')) {
            if (!str_starts_with($v, 'tel:')) {
                throw new BadRequestHttpException($key . ': ожидается ссылка вида tel:+7…');
            }

            return;
        }
        if (str_starts_with($key, 'email_')) {
            if (filter_var($v, FILTER_VALIDATE_EMAIL) === false) {
                throw new BadRequestHttpException($key . ': неверный e-mail.');
            }

            return;
        }
        if (str_starts_with($key, 'messenger_') || str_starts_with($key, 'map_')) {
            if (filter_var($v, FILTER_VALIDATE_URL) === false || !preg_match('#^https?://#i', $v)) {
                throw new BadRequestHttpException($key . ': нужен корректный URL (http/https).');
            }
        }
    }
}
