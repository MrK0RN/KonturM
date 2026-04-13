<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SiteContactsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class SiteContactsController
{
    public function __construct(
        private readonly SiteContactsService $siteContacts,
    ) {
    }

    #[Route('/api/site-contacts', name: 'api_site_contacts_get', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->siteContacts->getContacts());
    }
}
