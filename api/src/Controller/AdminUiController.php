<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Админка в public/admin/: если все запросы идут только в index.php (без router.php), статику отдаём здесь с верным Content-Type.
 */
#[AsController]
final class AdminUiController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    private function adminDir(): string
    {
        $dir = $this->projectDir . '/public/admin';
        $real = realpath($dir);
        if ($real === false || !is_dir($real)) {
            throw new NotFoundHttpException();
        }

        return $real;
    }

    // Не редиректить /admin → /admin/: Symfony сам отдаёт 301 /admin/ → /admin для канона без слэша — получался бесконечный цикл.
    #[Route('/admin', name: 'admin_ui_index', methods: ['GET'], priority: 16)]
    public function index(): BinaryFileResponse
    {
        return $this->fileResponse('index.html');
    }

    #[Route('/admin/{path}', name: 'admin_ui_asset', requirements: ['path' => '.+'], methods: ['GET'], priority: 16)]
    public function asset(string $path): BinaryFileResponse
    {
        return $this->fileResponse($path);
    }

    private function fileResponse(string $relative): BinaryFileResponse
    {
        if (str_contains($relative, '..')) {
            throw new NotFoundHttpException();
        }

        $base = $this->adminDir();
        $target = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $full = realpath($target);
        if ($full === false || !is_file($full)) {
            throw new NotFoundHttpException();
        }

        $baseReal = realpath($base);
        if ($baseReal === false || ($full !== $baseReal && !str_starts_with($full, $baseReal . DIRECTORY_SEPARATOR))) {
            throw new NotFoundHttpException();
        }

        $ext = strtolower((string) pathinfo($full, PATHINFO_EXTENSION));
        $allowed = ['css', 'js', 'html', 'svg', 'ico', 'map', 'json'];
        if (!in_array($ext, $allowed, true)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($full);
        $response->headers->set('Content-Type', $this->mimeType($ext));

        return $response;
    }

    private function mimeType(string $ext): string
    {
        return match ($ext) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8',
            'html' => 'text/html; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'map', 'json' => 'application/json; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }
}
