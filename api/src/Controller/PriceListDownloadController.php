<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PriceListFileService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class PriceListDownloadController
{
    public function __construct(
        private readonly PriceListFileService $priceList,
    ) {
    }

    #[Route(
        '/price-list.{ext}',
        name: 'storefront_price_list_file',
        requirements: ['ext' => 'xlsx|pdf'],
        methods: ['GET'],
        priority: 15,
    )]
    public function download(string $ext): BinaryFileResponse
    {
        $path = $this->priceList->resolveDownloadPath($ext);
        if ($path === null) {
            throw new NotFoundHttpException();
        }

        $ascii = 'price-list.' . $ext;

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $this->priceList->getContentType($ext));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $ascii);

        return $response;
    }
}
