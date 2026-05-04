<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\OrderCreateInput;
use App\Entity\Order;
use App\Service\Mail\CustomerOrderConfirmationMailBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
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
        private readonly CustomerOrderConfirmationMailBuilder $customerOrderMailBuilder,
        private readonly SiteContactsService $siteContacts,
        private readonly string $orderManagerNotificationEmail,
        private readonly LoggerInterface $logger,
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
        $order->setCustomerInn($input->customer_inn);
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
        $contacts = $this->siteContacts->getContacts();
        $from = new Address($contacts['email_sales'], 'Контур-М');
        $replyTo = new Address($contacts['email_sales'], 'Контур-М');

        $innLine = $order->getCustomerInn() !== null ? sprintf("\nИНН: %s", $order->getCustomerInn()) : '';
        $companyLine = $order->getCustomerCompany() !== null && trim($order->getCustomerCompany()) !== ''
            ? sprintf("\nКомпания: %s", $order->getCustomerCompany())
            : '';
        $commentLine = $order->getComment() !== null && trim($order->getComment()) !== ''
            ? sprintf("\nКомментарий: %s", $order->getComment())
            : '';
        $itemsBlock = $this->formatOrderItemsForManagerText($order);
        $managerBody = sprintf(
            "Новый заказ: %s\nКлиент: %s\nТелефон: %s\nEmail: %s%s%s%s\nСтатус: %s\n\nПозиции:\n%s\nИтого: %s ₽",
            $order->getOrderNumber(),
            $order->getCustomerName(),
            $order->getCustomerPhone(),
            $order->getCustomerEmail(),
            $companyLine,
            $innLine,
            $commentLine,
            $order->getStatus(),
            $itemsBlock,
            $order->getTotalAmount() ?? '0,00',
        );

        $managerEmail = (new Email())
            ->from($from)
            ->replyTo($replyTo)
            ->to($this->orderManagerNotificationEmail)
            ->subject('Новая заявка ' . $order->getOrderNumber())
            ->text($managerBody);

        try {
            $this->mailer->send($managerEmail);
        } catch (\Throwable $e) {
            $this->logger->error('Mail to manager failed after order', [
                'order_number' => $order->getOrderNumber(),
                'exception' => $e->getMessage(),
            ]);
        }

        $clientMail = $this->customerOrderMailBuilder->build($order, $contacts);
        $customerEmail = (new Email())
            ->from($from)
            ->replyTo($replyTo)
            ->to($order->getCustomerEmail())
            ->subject($clientMail['subject'])
            ->text($clientMail['text'])
            ->html($clientMail['html']);

        try {
            $this->mailer->send($customerEmail);
        } catch (\Throwable $e) {
            $this->logger->error('Mail to customer failed after order', [
                'order_number' => $order->getOrderNumber(),
                'customer_email' => $order->getCustomerEmail(),
                'exception' => $e->getMessage(),
            ]);
        }

        try {
            $notification = (new Notification('Новая заявка ' . $order->getOrderNumber(), ['chat/telegram']))
                ->content($managerBody);
            $this->notifier->send($notification, new Recipient($order->getCustomerEmail()));
        } catch (\Throwable) {
            // Keep order flow resilient in dev environments.
        }
    }

    private function formatOrderItemsForManagerText(Order $order): string
    {
        $lines = [];
        foreach ($order->getItems() as $i => $row) {
            if (! \is_array($row)) {
                continue;
            }
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '') {
                $name = sprintf('(%s)', $row['type'] ?? 'позиция');
            }
            $article = isset($row['article']) ? (string) $row['article'] : '—';
            $qty = isset($row['quantity']) ? (int) $row['quantity'] : 0;
            $price = isset($row['price']) && $row['price'] !== null && $row['price'] !== ''
                ? (string) $row['price']
                : '—';
            $lines[] = sprintf('%d. %s | арт. %s | кол-во %d | цена %s', $i + 1, $name, $article, $qty, $price);
        }

        return $lines === [] ? '—' : implode("\n", $lines);
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

