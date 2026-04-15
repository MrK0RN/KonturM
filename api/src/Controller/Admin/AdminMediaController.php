<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\MediaGalleryService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/api/admin/media')]
#[IsGranted('ROLE_ADMIN')]
final class AdminMediaController
{
    public function __construct(
        private readonly MediaGalleryService $gallery,
    ) {
    }

    #[Route('', name: 'admin_media_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $ownerType = (string) $request->query->get('owner_type', '');
        $ownerId = (string) $request->query->get('owner_id', '');
        if ($ownerType === '' || $ownerId === '') {
            return new JsonResponse(['detail' => 'Укажите owner_type и owner_id'], 400);
        }

        try {
            $items = $this->gallery->listAssets($ownerType, $ownerId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['detail' => $e->getMessage()], 400);
        }

        return new JsonResponse(['items' => $items]);
    }

    #[Route('', name: 'admin_media_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $ownerType = (string) ($request->request->get('owner_type') ?: $request->query->get('owner_type', ''));
        $ownerId = (string) ($request->request->get('owner_id') ?: $request->query->get('owner_id', ''));
        $alt = $request->request->get('alt');
        $altStr = is_string($alt) ? $alt : null;

        $file = $request->files->get('file');
        if ($ownerType === '' || $ownerId === '') {
            return new JsonResponse(['detail' => 'Укажите owner_type и owner_id (в форме или в query).'], 400);
        }

        if (! $file instanceof UploadedFile) {
            return new JsonResponse(['detail' => 'Нужен файл в поле file.'], 400);
        }

        if (! $file->isValid()) {
            return new JsonResponse(['detail' => $file->getErrorMessage()], 400);
        }

        try {
            $item = $this->gallery->upload($ownerType, $ownerId, $file, $altStr);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['detail' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['detail' => $e->getMessage()], 500);
        }

        return new JsonResponse($item, 201);
    }

    #[Route('/reorder', name: 'admin_media_reorder', methods: ['POST'], priority: 10)]
    public function reorder(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        if (! is_array($data)) {
            return new JsonResponse(['detail' => 'Ожидается JSON'], 400);
        }

        $ownerType = isset($data['owner_type']) && is_string($data['owner_type']) ? $data['owner_type'] : '';
        $ownerId = isset($data['owner_id']) && is_string($data['owner_id']) ? $data['owner_id'] : '';
        $ordered = $data['ordered_ids'] ?? null;
        if ($ownerType === '' || $ownerId === '' || ! is_array($ordered)) {
            return new JsonResponse(['detail' => 'Нужны owner_type, owner_id и ordered_ids[]'], 400);
        }

        $ids = [];
        foreach ($ordered as $id) {
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        try {
            $this->gallery->reorder($ownerType, $ownerId, $ids);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['detail' => $e->getMessage()], 400);
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/{id}', name: 'admin_media_patch', methods: ['PATCH'], requirements: ['id' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}'])]
    public function patch(string $id, Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        if (! is_array($data)) {
            return new JsonResponse(['detail' => 'Ожидается JSON'], 400);
        }

        $patch = [];
        if (array_key_exists('alt', $data)) {
            $patch['alt'] = $data['alt'];
        }
        if (array_key_exists('is_primary', $data) && is_bool($data['is_primary'])) {
            $patch['is_primary'] = $data['is_primary'];
        }

        if ($patch === []) {
            return new JsonResponse(['detail' => 'Нет полей для обновления (alt, is_primary)'], 400);
        }

        try {
            $item = $this->gallery->patch($id, $patch);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['detail' => $e->getMessage()], 404);
        }

        return new JsonResponse($item);
    }

    #[Route('/{id}', name: 'admin_media_delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}'])]
    public function delete(string $id): Response
    {
        try {
            $this->gallery->delete($id);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['detail' => $e->getMessage()], 404);
        }

        return new Response('', 204);
    }
}
