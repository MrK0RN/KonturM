<?php

declare(strict_types=1);

/**
 * Роутер для встроенного сервера PHP: сначала отдаём существующие файлы из public/ как статику
 * (корректный Content-Type для .css/.js). Иначе — в Symfony (index.php).
 *
 * Запуск: php -S 127.0.0.1:8000 -t public public/router.php
 */
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '' && $uri !== '/' && !str_contains($uri, '..')) {
    $file = __DIR__ . $uri;
    if (is_file($file)) {
        return false;
    }
}

// Встроенный сервер оставляет SCRIPT_FILENAME на запрошенном пути (например admin/index.html);
// Symfony Runtime обязан грузить front controller — иначе require вернёт 1 для статики.
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

require __DIR__ . '/index.php';
