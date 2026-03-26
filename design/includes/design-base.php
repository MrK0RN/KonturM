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
