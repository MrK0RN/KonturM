<?php

declare(strict_types=1);

namespace App\Controller\Orders;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final class OrderPatchStatusController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(string $id, Request $request): Order
    {
        /** @var Order|null $order */
        $order = $this->entityManager->getRepository(Order::class)->find($id);
        if ($order === null) {
            throw new NotFoundHttpException(sprintf('Order "%s" not found.', $id));
        }

        $payload = json_decode((string) $request->getContent(), true);
        $status = is_array($payload) ? ($payload['status'] ?? null) : null;
        if (! is_string($status) || ! in_array($status, ['new', 'processing', 'completed', 'cancelled'], true)) {
            throw new BadRequestHttpException('Invalid status.');
        }

        $order->setStatus($status);
        $this->entityManager->flush();

        return $order;
    }
}

