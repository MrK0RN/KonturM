<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Отдаёт статические HTML из public/, когда веб-сервер проксирует всё в index.php
 * и файлы .html не раздаются напрямую (иначе 404).
 */
#[AsController]
final class TestUiController
{
    private const ALLOWED = [
        'demo-shop.html',
        'api-tester.html',
        'test-pages.html',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/demo-shop.html', name: 'test_ui_demo_shop', methods: ['GET'])]
    public function demoShop(): Response
    {
        return $this->serve('demo-shop.html');
    }

    #[Route('/api-tester.html', name: 'test_ui_api_tester', methods: ['GET'])]
    public function apiTester(): Response
    {
        return $this->serve('api-tester.html');
    }

    #[Route('/test-pages.html', name: 'test_ui_index', methods: ['GET'])]
    public function testPages(): Response
    {
        return $this->serve('test-pages.html');
    }

    private function serve(string $filename): Response
    {
        if (!in_array($filename, self::ALLOWED, true)) {
            throw new NotFoundHttpException();
        }

        $path = $this->projectDir . '/public/' . $filename;
        if (!is_readable($path)) {
            throw new NotFoundHttpException(sprintf('File "%s" not found.', $filename));
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new NotFoundHttpException();
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
