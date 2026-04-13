<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SiteContactsService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Верстка из репозитория /design (рядом с api/), не из public/.
 * URL в браузере: /design/catalog.php — относительные пути css/... резолвятся в /design/css/...
 */
#[AsController]
final class DesignCatalogController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly SiteContactsService $siteContacts,
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

    #[Route('/catalog', name: 'design_catalog_redirect', methods: ['GET'])]
    public function catalogRedirect(): RedirectResponse
    {
        return new RedirectResponse('/', Response::HTTP_FOUND);
    }

    private const PHP_PAGES = ['catalog.php', 'index.php'];

    #[Route('/', name: 'home_catalog', methods: ['GET'], priority: 20)]
    public function homeCatalog(Request $request): Response
    {
        return $this->renderDesignPhpFile($request, 'catalog.php');
    }

    #[Route(
        '/design/{page}',
        name: 'design_php_page',
        requirements: ['page' => 'catalog\.php|index\.php'],
        methods: ['GET'],
        priority: 10
    )]
    public function designPhpPage(Request $request, string $page): Response
    {
        return $this->renderDesignPhpFile($request, $page);
    }

    private function renderDesignPhpFile(Request $request, string $page): Response
    {
        if (!in_array($page, self::PHP_PAGES, true)) {
            throw new NotFoundHttpException();
        }

        $file = $this->designRoot() . '/' . $page;
        if (!is_readable($file)) {
            throw new NotFoundHttpException();
        }

        $GLOBALS['KONTURM_DESIGN_BASE'] = rtrim($this->designUrlPrefix($request), '/');
        $GLOBALS['KONTURM_REQUEST_BASE_PATH'] = rtrim($request->getBasePath(), '/');
        $GLOBALS['KONTURM_SITE_CONTACTS'] = $this->siteContacts->getContacts();
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            unset($GLOBALS['KONTURM_DESIGN_BASE'], $GLOBALS['KONTURM_REQUEST_BASE_PATH'], $GLOBALS['KONTURM_SITE_CONTACTS']);
            throw $e;
        }
        $html = ob_get_clean();
        unset($GLOBALS['KONTURM_DESIGN_BASE'], $GLOBALS['KONTURM_REQUEST_BASE_PATH'], $GLOBALS['KONTURM_SITE_CONTACTS']);
        $html = $this->rewriteDesignStaticUrls($request, $html);

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Абсолютные пути от корня сайта: на главной (/) относительные css/ и assets/
     * иначе превращаются в /css/... и не попадают в маршрут /design/{path}.
     */
    private function designUrlPrefix(Request $request): string
    {
        $bp = rtrim($request->getBasePath(), '/');

        return ($bp === '' ? '' : $bp) . '/design/';
    }

    private function rewriteDesignStaticUrls(Request $request, string $html): string
    {
        $p = $this->designUrlPrefix($request);
        $html = (string) preg_replace('/href=(["\'])css\//', 'href=$1' . $p . 'css/', $html);
        $html = (string) preg_replace('/src=(["\'])assets\//', 'src=$1' . $p . 'assets/', $html);

        return $html;
    }

    #[Route('/design/{path}', name: 'design_static_asset', requirements: ['path' => '.+'], methods: ['GET'], priority: -100)]
    public function staticAsset(string $path): BinaryFileResponse
    {
        if (str_contains($path, '..')) {
            throw new NotFoundHttpException();
        }

        $base = $this->designRoot();
        $target = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $full = realpath($target);
        if ($full === false || !is_file($full)) {
            throw new NotFoundHttpException();
        }

        $baseReal = realpath($base);
        if ($baseReal === false) {
            throw new NotFoundHttpException();
        }
        if ($full !== $baseReal && !str_starts_with($full, $baseReal . DIRECTORY_SEPARATOR)) {
            throw new NotFoundHttpException();
        }

        $ext = strtolower((string) pathinfo($full, PATHINFO_EXTENSION));
        $allowed = ['css', 'js', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'map', 'json'];
        if (!in_array($ext, $allowed, true)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($full);
        // finfo часто даёт text/plain для .css — браузер не применяет как stylesheet без text/css
        $response->headers->set('Content-Type', $this->designAssetMimeType($ext));
        $response->setPublic();
        $response->headers->set('Cache-Control', 'public, max-age=604800');

        return $response;
    }

    private function designAssetMimeType(string $ext): string
    {
        return match ($ext) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'map', 'json' => 'application/json; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }
}
