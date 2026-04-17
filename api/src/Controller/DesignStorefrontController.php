<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CertificatesCatalogService;
use App\Service\SiteContactsService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Дополнительные страницы витрины (design/*.php рядом с api).
 */
#[AsController]
final class DesignStorefrontController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly SiteContactsService $siteContacts,
        private readonly CertificatesCatalogService $certificatesCatalog,
    ) {
    }

    private function designRoot(): string
    {
        $dir = dirname($this->projectDir) . '/design';
        $real = realpath($dir);
        if ($real === false || !is_dir($real)) {
            throw new \RuntimeException('Папка design не найдена рядом с api: ' . $dir);
        }

        return $real;
    }

    private function designUrlPrefix(Request $request): string
    {
        $bp = rtrim($request->getBasePath(), '/');

        return ($bp === '' ? '' : $bp) . '/design/';
    }

    private function render(string $relativePhp, Request $request): Response
    {
        $file = $this->designRoot() . '/' . ltrim($relativePhp, '/');
        $real = realpath($file);
        if ($real === false || !str_starts_with($real, $this->designRoot()) || !is_readable($real)) {
            throw new NotFoundHttpException();
        }

        $GLOBALS['KONTURM_DESIGN_BASE'] = rtrim($this->designUrlPrefix($request), '/');
        $GLOBALS['KONTURM_REQUEST_BASE_PATH'] = rtrim($request->getBasePath(), '/');
        $GLOBALS['KONTURM_SITE_CONTACTS'] = $this->siteContacts->getContacts();
        $GLOBALS['KONTURM_CERTIFICATES_CATALOG'] = $this->certificatesCatalog->getCatalog();
        ob_start();
        try {
            include $real;
        } catch (\Throwable $e) {
            ob_end_clean();
            unset(
                $GLOBALS['KONTURM_DESIGN_BASE'],
                $GLOBALS['KONTURM_REQUEST_BASE_PATH'],
                $GLOBALS['KONTURM_SITE_CONTACTS'],
                $GLOBALS['KONTURM_CERTIFICATES_CATALOG'],
            );
            throw $e;
        }
        $html = ob_get_clean();
        unset(
            $GLOBALS['KONTURM_DESIGN_BASE'],
            $GLOBALS['KONTURM_REQUEST_BASE_PATH'],
            $GLOBALS['KONTURM_SITE_CONTACTS'],
            $GLOBALS['KONTURM_CERTIFICATES_CATALOG'],
        );

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    #[Route('/cart', name: 'design_cart', methods: ['GET'], priority: 14)]
    public function cart(Request $request): Response
    {
        return $this->render('cart.php', $request);
    }

    #[Route('/search', name: 'design_search', methods: ['GET'], priority: 14)]
    public function search(Request $request): Response
    {
        return $this->render('search.php', $request);
    }

    #[Route('/product', name: 'design_product', methods: ['GET'], priority: 14)]
    public function product(Request $request): Response
    {
        return $this->render('product.php', $request);
    }

    #[Route('/about', name: 'design_about', methods: ['GET'], priority: 14)]
    public function about(Request $request): Response
    {
        return $this->render('about.php', $request);
    }

    #[Route('/contacts', name: 'design_contacts', methods: ['GET'], priority: 14)]
    public function contacts(Request $request): Response
    {
        return $this->render('contacts.php', $request);
    }

    #[Route('/promo', name: 'design_promo', methods: ['GET'], priority: 14)]
    public function promo(Request $request): Response
    {
        return $this->render('promo.php', $request);
    }

    #[Route('/price-list', name: 'design_price_list', methods: ['GET'], priority: 14)]
    public function priceList(Request $request): Response
    {
        return $this->render('price-list.php', $request);
    }

    #[Route('/certificates', name: 'design_certificates', methods: ['GET'], priority: 14)]
    public function certificates(Request $request): Response
    {
        return $this->render('certificates.php', $request);
    }
}
