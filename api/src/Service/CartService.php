<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\OrderCreateInput;

final class CartService
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = dirname(__DIR__, 2) . '/var/cart_items.json';
    }

    public function getCart(): array
    {
        $items = $this->readItems();

        return $this->normalizeCart($items);
    }

    public function addItem(array $item): array
    {
        $items = $this->readItems();

        $id = $item['id'];
        $type = $item['type'];
        $quantity = (int) $item['quantity'];

        $key = $type . ':' . $id;
        if (isset($items[$key])) {
            $items[$key]['quantity'] += $quantity;
        } else {
            $items[$key] = [
                'id' => $key,
                'type' => $type,
                'entity_id' => $id,
                'name' => $item['name'] ?? null,
                'article' => $item['article'] ?? null,
                'slug' => $item['slug'] ?? null,
                'photo' => $item['photo'] ?? null,
                'quantity' => $quantity,
                'price' => $item['price'] ?? null,
            ];
        }

        $this->writeItems($items);

        return $this->normalizeCart($items);
    }

    public function updateItem(string $itemId, int $quantity): array
    {
        $items = $this->readItems();
        if (! isset($items[$itemId])) {
            return $this->normalizeCart($items);
        }

        if ($quantity <= 0) {
            unset($items[$itemId]);
        } else {
            $items[$itemId]['quantity'] = $quantity;
        }

        $this->writeItems($items);

        return $this->normalizeCart($items);
    }

    public function removeItem(string $itemId): array
    {
        $items = $this->readItems();
        unset($items[$itemId]);
        $this->writeItems($items);

        return $this->normalizeCart($items);
    }

    public function clear(): array
    {
        $this->writeItems([]);

        return $this->normalizeCart([]);
    }

    public function buildOrderInputFromCart(array $checkoutPayload): OrderCreateInput
    {
        $items = array_values($this->readItems());

        $input = new OrderCreateInput();
        $input->customer_name = $checkoutPayload['customer_name'] ?? null;
        $input->customer_company = $checkoutPayload['customer_company'] ?? null;
        $input->customer_phone = $checkoutPayload['customer_phone'] ?? null;
        $input->customer_email = $checkoutPayload['customer_email'] ?? null;
        $input->comment = $checkoutPayload['comment'] ?? null;
        $input->attachments = $checkoutPayload['attachments'] ?? null;
        $input->items = array_map(static function (array $item): array {
            return [
                'type' => $item['type'],
                'id' => $item['entity_id'],
                'name' => $item['name'],
                'article' => $item['article'],
                'quantity' => (int) $item['quantity'],
                'price' => $item['price'] !== null ? (float) $item['price'] : null,
            ];
        }, $items);

        return $input;
    }

    private function normalizeCart(array $itemsAssoc): array
    {
        $items = array_values($itemsAssoc);
        $totalQuantity = 0;
        $totalAmount = 0.0;

        $normalizedItems = array_map(static function (array $item) use (&$totalQuantity, &$totalAmount) {
            $qty = (int) ($item['quantity'] ?? 0);
            $price = isset($item['price']) ? (float) $item['price'] : 0.0;
            $total = $qty * $price;

            $totalQuantity += $qty;
            $totalAmount += $total;

            return [
                'id' => $item['id'],
                'type' => $item['type'],
                ($item['type'] === 'product' ? 'product_id' : 'service_id') => $item['entity_id'],
                'name' => $item['name'],
                'article' => $item['article'],
                'slug' => $item['slug'],
                'photo' => $item['photo'],
                'quantity' => $qty,
                'price' => $item['price'],
                'total' => $total,
            ];
        }, $items);

        return [
            'items' => $normalizedItems,
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
        ];
    }

    private function readItems(): array
    {
        if (!is_file($this->storagePath)) {
            return [];
        }

        $raw = (string) @file_get_contents($this->storagePath);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeItems(array $items): void
    {
        @file_put_contents($this->storagePath, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

