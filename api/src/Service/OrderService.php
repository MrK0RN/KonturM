<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\OrderCreateInput;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

final class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly NotifierInterface $notifier,
    ) {
    }

    public function createOrder(OrderCreateInput $input): Order
    {
        $order = new Order();
        $order->setOrderNumber($this->generateOrderNumber());
        $order->setCustomerName((string) $input->customer_name);
        $order->setCustomerCompany($input->customer_company);
        $order->setCustomerPhone((string) $input->customer_phone);
        $order->setCustomerEmail((string) $input->customer_email);
        $order->setComment($input->comment);
        $order->setAttachments($input->attachments);
        $order->setStatus('new');

        $items = array_map(static fn (mixed $item) => (array) $item, $input->items);
        $order->setItems($items);

        $total = 0.0;
        foreach ($items as $item) {
            $price = isset($item['price']) ? (float) $item['price'] : 0.0;
            $qty = isset($item['quantity']) ? (int) $item['quantity'] : 0;
            $total += $price * $qty;
        }
        $order->setTotalAmount(number_format($total, 2, '.', ''));

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->sendNotifications($order);

        return $order;
    }

    public function buildStatusPayload(Order $order): array
    {
        return [
            'order_number' => $order->getOrderNumber(),
            'status' => $order->getStatus(),
            'status_text' => $this->statusText($order->getStatus()),
            'created_at' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function generateOrderNumber(): string
    {
        $year = (new \DateTimeImmutable())->format('Y');
        $prefix = sprintf('M-%s-', $year);

        $conn = $this->entityManager->getConnection();
        $lastSeq = (int) $conn->fetchOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(order_number FROM 8) AS INTEGER)), 0) FROM orders WHERE order_number LIKE :prefix",
            ['prefix' => $prefix . '%'],
        );

        $next = $lastSeq + 1;

        return sprintf('M-%s-%05d', $year, $next);
    }

    private function sendNotifications(Order $order): void
    {
        $body = sprintf(
            "Новый заказ: %s\nКлиент: %s\nТелефон: %s\nEmail: %s\nСтатус: %s",
            $order->getOrderNumber(),
            $order->getCustomerName(),
            $order->getCustomerPhone(),
            $order->getCustomerEmail(),
            $order->getStatus()
        );

        $email = (new Email())
            ->from('noreply@merniki.local')
            ->to('manager@merniki.local')
            ->subject('Новая заявка ' . $order->getOrderNumber())
            ->text($body);

        try {
            $this->mailer->send($email);
        } catch (\Throwable) {
            // Keep order flow resilient in dev environments.
        }

        try {
            $notification = (new Notification('Новая заявка ' . $order->getOrderNumber(), ['chat/telegram']))
                ->content($body);
            $this->notifier->send($notification, new Recipient($order->getCustomerEmail()));
        } catch (\Throwable) {
            // Keep order flow resilient in dev environments.
        }
    }

    private function statusText(string $status): string
    {
        return match ($status) {
            'processing' => 'Ваш заказ обрабатывается',
            'completed' => 'Ваш заказ выполнен',
            'cancelled' => 'Ваш заказ отменен',
            default => 'Ваш заказ принят',
        };
    }
}

