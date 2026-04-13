<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PriceListFileService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/api/admin/price-list')]
#[IsGranted('ROLE_ADMIN')]
final class AdminPriceListController
{
    public function __construct(
        private readonly PriceListFileService $priceList,
    ) {
    }

    #[Route('', name: 'admin_price_list_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse($this->priceList->getAdminInfo());
    }

    #[Route('', name: 'admin_price_list_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (! $file instanceof UploadedFile) {
            return new JsonResponse(['detail' => 'Нужен файл в поле file'], 400);
        }

        try {
            $info = $this->priceList->save($file);
        } catch (HttpExceptionInterface $e) {
            return new JsonResponse(['detail' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse(['detail' => $e->getMessage()], 500);
        }

        return new JsonResponse($info);
    }

    #[Route('', name: 'admin_price_list_delete', methods: ['DELETE'])]
    public function delete(): JsonResponse
    {
        $this->priceList->clear();

        return new JsonResponse($this->priceList->getAdminInfo());
    }
}
