<?php
declare(strict_types=1);

if (!function_exists('konturm_design_url')) {
    /**
     * Пути к css/ и assets/ для страниц в /design/.
     * Symfony задаёт $GLOBALS['KONTURM_DESIGN_BASE'] на «/»; при прямом открытии *.php — относительные пути.
     */
    function konturm_design_url(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $base = $GLOBALS['KONTURM_DESIGN_BASE'] ?? '';
        if ($base === '') {
            return $path;
        }

        return $base . '/' . $path;
    }
}

if (!function_exists('konturm_design_pages_asset')) {
    /**
     * Статика внутри design/pages/{pageDir}/ (например category2/css/...).
     * При прямом открытии *.php без Symfony — относительно каталога pages/.
     */
    function konturm_design_pages_asset(string $pageDir, string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $pageDir = trim(str_replace('\\', '/', $pageDir), '/');
        $base = $GLOBALS['KONTURM_DESIGN_BASE'] ?? '';
        if ($base === '') {
            return $pageDir . '/' . $path;
        }

        return $base . '/pages/' . $pageDir . '/' . $path;
    }
}

if (!function_exists('konturm_site_contacts')) {
    /**
     * Контакты витрины: из API при рендере через Symfony ($GLOBALS['KONTURM_SITE_CONTACTS']),
     * иначе значения по умолчанию (синхронизировать с App\Service\SiteContactsService::DEFAULTS).
     *
     * @return array<string, string>
     */
    function konturm_site_contacts(): array
    {
        $g = $GLOBALS['KONTURM_SITE_CONTACTS'] ?? null;
        if (is_array($g)) {
            return $g;
        }

        return [
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
            'map_iframe_src' => 'https://yandex.ru/map-widget/v1/?ll=49.276146%2C55.912513&z=18&pt=49.275821%2C55.912601%2Cpm2rdm',
            'map_yandex_link' => 'https://yandex.ru/maps/?from=mapframe&ll=49.276146%2C55.912513&mode=poi&poi%5Bpoint%5D=49.275821%2C55.912601&poi%5Buri%5D=ymapsbm1%3A%2F%2Forg%3Foid%3D92963604301&source=mapframe&utm_source=mapframe&z=17.59',
        ];
    }
}
