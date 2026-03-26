<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartService;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class AddCartItemController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        $type = $payload['type'] ?? null;
        $id = $payload['id'] ?? null;
        $quantity = (int) ($payload['quantity'] ?? 1);

        if (!in_array($type, ['product', 'service'], true) || !is_string($id) || $quantity <= 0) {
            throw new BadRequestHttpException('Payload must contain valid type, id and positive quantity.');
        }

        if ($type === 'product') {
            $row = $this->connection->fetchAssociative(
                'SELECT id, name, article, slug, photo, price FROM products WHERE id = :id LIMIT 1',
                ['id' => $id]
            );
        } else {
            $row = $this->connection->fetchAssociative(
                'SELECT id, name, slug, photo, price FROM services WHERE id = :id LIMIT 1',
                ['id' => $id]
            );
        }

        if ($row === false) {
            throw new BadRequestHttpException('Entity not found.');
        }

        return $this->cartService->addItem([
            'type' => $type,
            'id' => $id,
            'quantity' => $quantity,
            'name' => $row['name'],
            'article' => $row['article'] ?? null,
            'slug' => $row['slug'] ?? null,
            'photo' => $row['photo'] ?? null,
            'price' => $row['price'] ?? null,
        ]);
    }
}

