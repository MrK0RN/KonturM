<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\CertificatesCatalogService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/api/admin/certificates-catalog')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCertificatesCatalogController
{
    public function __construct(
        private readonly CertificatesCatalogService $catalog,
    ) {
    }

    #[Route('/files', name: 'admin_certificates_catalog_files', methods: ['GET'])]
    public function listFiles(): JsonResponse
    {
        return new JsonResponse(['files' => $this->catalog->listDocumentPdfNames()]);
    }

    #[Route('', name: 'admin_certificates_catalog_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        $data = $this->catalog->getCatalog();
        $files = $this->catalog->listDocumentPdfNames();
        $fileSet = array_fill_keys($files, true);
        $missing = [];
        foreach ($data['groups'] as $g) {
            foreach ($g['items'] as $it) {
                $fn = $it['filename'] ?? '';
                if ($fn !== '' && !isset($fileSet[$fn])) {
                    $missing[] = $fn;
                }
            }
        }

        return new JsonResponse(array_merge($data, [
            'files' => $files,
            'missing_files' => array_values(array_unique($missing)),
        ]));
    }

    #[Route('', name: 'admin_certificates_catalog_put', methods: ['PUT'])]
    public function put(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['detail' => 'Ожидается JSON-объект'], 400);
        }

        $saved = $this->catalog->save($data);

        return new JsonResponse($saved);
    }

    #[Route('/upload', name: 'admin_certificates_catalog_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['detail' => 'Нужен файл в поле file'], 400);
        }

        try {
            $info = $this->catalog->saveUploadedPdf($file);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return new JsonResponse(['detail' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse(['detail' => $e->getMessage()], 500);
        }

        return new JsonResponse($info, 201);
    }
}
