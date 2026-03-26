<?php

declare(strict_types=1);

namespace App\Controller\Orders;

use App\Dto\OrderCreateInput;
use App\Service\OrderService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
final class CreateOrderController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly OrderService $orderService,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $raw = (string) $request->getContent();
        /** @var OrderCreateInput $input */
        $input = $this->serializer->deserialize($raw, OrderCreateInput::class, 'json');

        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw new BadRequestHttpException((string) $violations);
        }

        $order = $this->orderService->createOrder($input);

        return [
            'order_number' => $order->getOrderNumber(),
            'status' => $order->getStatus(),
        ];
    }
}

