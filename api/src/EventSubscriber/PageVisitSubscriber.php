<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\PageVisit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Records HTML page views for the public storefront (not API/admin/assets).
 */
final class PageVisitSubscriber implements EventSubscriberInterface
{
    private const EXCLUDED_PREFIXES = [
        '/api',
        '/admin',
        '/_profiler',
        '/_wdt',
        '/.well-known',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$this->shouldRecord($request, $response)) {
            return;
        }

        $path = $request->getPathInfo();
        if ($path === '') {
            $path = '/';
        }
        if (strlen($path) > 2048) {
            $path = substr($path, 0, 2048);
        }

        try {
            $this->entityManager->persist(new PageVisit($path));
            $this->entityManager->flush();
        } catch (\Throwable) {
            // Do not break the storefront if the DB is unavailable.
        }
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        if ($request->getMethod() !== Request::METHOD_GET) {
            return false;
        }
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return false;
        }

        $path = $request->getPathInfo();
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        $ct = strtolower((string) $response->headers->get('Content-Type', ''));
        if (!str_contains($ct, 'text/html')) {
            return false;
        }

        if ($request->headers->get('Sec-Purpose') === 'prefetch') {
            return false;
        }

        return true;
    }
}
