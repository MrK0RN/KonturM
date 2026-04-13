<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SiteContactsService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Страница category2: design/pages/category2.php (шапка/подвал через include).
 * Статика: GET /design/{path} → KonturM/design/.
 */
#[AsController]
final class DesignCategory2Controller
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly SiteContactsService $siteContacts,
    ) {
    }

    #[Route('/category2', name: 'design_category2', methods: ['GET'], priority: 12)]
    #[Route('/design/pages/category2.php', name: 'design_category2_php', methods: ['GET'], priority: 11)]
    public function __invoke(Request $request): Response
    {
        $file = dirname($this->projectDir) . '/design/pages/category2.php';
        $real = realpath($file);
        if ($real === false || !is_readable($real)) {
            throw new NotFoundHttpException();
        }

        $bp = rtrim($request->getBasePath(), '/');
        $GLOBALS['KONTURM_DESIGN_BASE'] = ($bp === '' ? '' : $bp) . '/design';
        $GLOBALS['KONTURM_REQUEST_BASE_PATH'] = rtrim($request->getBasePath(), '/');
        $GLOBALS['KONTURM_SITE_CONTACTS'] = $this->siteContacts->getContacts();

        ob_start();
        try {
            include $real;
        } catch (\Throwable $e) {
            ob_end_clean();
            unset($GLOBALS['KONTURM_DESIGN_BASE'], $GLOBALS['KONTURM_REQUEST_BASE_PATH'], $GLOBALS['KONTURM_SITE_CONTACTS']);
            throw $e;
        }
        $html = ob_get_clean();
        unset($GLOBALS['KONTURM_DESIGN_BASE'], $GLOBALS['KONTURM_REQUEST_BASE_PATH'], $GLOBALS['KONTURM_SITE_CONTACTS']);

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
