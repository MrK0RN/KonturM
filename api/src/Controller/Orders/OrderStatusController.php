<?php

declare(strict_types=1);

namespace App\Controller\Orders;

use App\Entity\Order;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final class OrderStatusController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderService $orderService,
    ) {
    }

    public function __invoke(string $order_number, Request $request): array
    {
        $repo = $this->entityManager->getRepository(Order::class);
        /** @var Order|null $order */
        $order = $repo->findOneBy(['orderNumber' => $order_number]);
        if ($order === null) {
            throw new NotFoundHttpException(sprintf('Order "%s" not found.', $order_number));
        }

        return $this->orderService->buildStatusPayload($order);
    }
}

