<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SiteContactsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/api/admin/site-contacts')]
#[IsGranted('ROLE_ADMIN')]
final class AdminSiteContactsController
{
    public function __construct(
        private readonly SiteContactsService $siteContacts,
    ) {
    }

    #[Route('', name: 'admin_site_contacts_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse($this->siteContacts->getContacts());
    }

    #[Route('', name: 'admin_site_contacts_put', methods: ['PUT'])]
    public function put(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['detail' => 'Ожидается JSON-объект'], 400);
        }

        $saved = $this->siteContacts->save($data);

        return new JsonResponse($saved);
    }
}
