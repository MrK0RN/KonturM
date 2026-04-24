<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DocumentsPathService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Отдаёт PDF-файлы из папки documents/ рядом с api/.
 */
#[AsController]
final class DocumentsController
{
    public function __construct(
        private readonly DocumentsPathService $documentsPath,
    ) {
    }

    private function documentsDir(): string
    {
        $real = $this->documentsPath->resolveDirectory();
        if ($real === null) {
            throw new NotFoundHttpException();
        }

        return $real;
    }

    #[Route('/documents/{filename}', name: 'documents_file', requirements: ['filename' => '[^/]+'], methods: ['GET'], priority: 14)]
    public function file(string $filename): BinaryFileResponse
    {
        if (str_contains($filename, '..') || str_contains($filename, '/')) {
            throw new NotFoundHttpException();
        }

        $base = $this->documentsDir();
        $target = $base . DIRECTORY_SEPARATOR . $filename;
        $full = realpath($target);

        if ($full === false || !is_file($full)) {
            throw new NotFoundHttpException();
        }

        if (!str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
            throw new NotFoundHttpException();
        }

        $ext = strtolower((string) pathinfo($full, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($full);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename,
        );

        return $response;
    }
}
