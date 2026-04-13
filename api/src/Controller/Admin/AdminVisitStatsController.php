<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\PageVisitRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/api/admin/visit-stats')]
#[IsGranted('ROLE_ADMIN')]
final class AdminVisitStatsController
{
    public function __construct(
        private readonly PageVisitRepository $pageVisits,
    ) {
    }

    #[Route('', name: 'admin_visit_stats', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', '30');
        $top = (int) $request->query->get('top', '25');

        return new JsonResponse($this->pageVisits->getAdminStats($days, $top));
    }
}
