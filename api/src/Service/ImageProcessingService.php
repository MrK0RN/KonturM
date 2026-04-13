<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Decodes raster images, scales down, writes full + thumbnail.
 * Prefers WebP when GD supports it; otherwise JPEG (servers without libwebp).
 */
final class ImageProcessingService
{
    private const FULL_MAX = 1920;

    private const THUMB_MAX = 400;

    private const WEBP_QUALITY = 85;

    private const JPEG_QUALITY = 85;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{full_absolute: string, thumb_absolute: string, full_web_path: string, thumb_web_path: string, width: int, height: int}
     */
    public function writeWebpVariants(
        string $binary,
        string $ownerType,
        string $ownerId,
        string $assetId,
    ): array {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('PHP extension gd is required for image processing.');
        }

        $useWebp = \function_exists('imagewebp');
        $suffix = $useWebp ? '_full.webp' : '_full.jpg';
        $thumbSuffix = $useWebp ? '_thumb.webp' : '_thumb.jpg';

        $img = @\imagecreatefromstring($binary);
        if ($img === false) {
            throw new \InvalidArgumentException('Файл не является поддерживаемым изображением.');
        }

        if (\function_exists('imagepalettetotruecolor') && ! \imageistruecolor($img)) {
            \imagepalettetotruecolor($img);
        }
        \imagealphablending($img, true);
        \imagesavealpha($img, true);

        $w = \imagesx($img);
        $h = \imagesy($img);
        if ($w < 1 || $h < 1) {
            \imagedestroy($img);
            throw new \InvalidArgumentException('Некорректный размер изображения.');
        }

        $full = $this->resizeMaxSide($img, self::FULL_MAX);
        $thumb = $this->resizeMaxSide($img, self::THUMB_MAX);
        \imagedestroy($img);

        $dir = $this->projectDir.'/public/media/'.$ownerType.'/'.$ownerId;
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            \imagedestroy($full);
            \imagedestroy($thumb);
            throw new \RuntimeException('Не удалось создать каталог для медиа.');
        }

        $base = $dir.'/'.$assetId;
        $fullAbs = $base.$suffix;
        $thumbAbs = $base.$thumbSuffix;

        if ($useWebp) {
            $ok = \imagewebp($full, $fullAbs, self::WEBP_QUALITY) && \imagewebp($thumb, $thumbAbs, self::WEBP_QUALITY);
            \imagedestroy($full);
            \imagedestroy($thumb);
            if (! $ok) {
                throw new \RuntimeException('Не удалось сохранить WebP.');
            }
        } else {
            if (! \function_exists('imagejpeg')) {
                \imagedestroy($full);
                \imagedestroy($thumb);
                throw new \RuntimeException('PHP GD must support WebP (imagewebp) or JPEG (imagejpeg) for image output.');
            }
            $fullFlat = $this->flattenAlphaToWhite($full);
            $thumbFlat = $this->flattenAlphaToWhite($thumb);
            \imagedestroy($full);
            \imagedestroy($thumb);
            $ok = \imagejpeg($fullFlat, $fullAbs, self::JPEG_QUALITY) && \imagejpeg($thumbFlat, $thumbAbs, self::JPEG_QUALITY);
            \imagedestroy($fullFlat);
            \imagedestroy($thumbFlat);
            if (! $ok) {
                throw new \RuntimeException('Не удалось сохранить JPEG.');
            }
        }

        $fullWeb = '/media/'.$ownerType.'/'.$ownerId.'/'.$assetId.$suffix;
        $thumbWeb = '/media/'.$ownerType.'/'.$ownerId.'/'.$assetId.$thumbSuffix;

        return [
            'full_absolute' => $fullAbs,
            'thumb_absolute' => $thumbAbs,
            'full_web_path' => $fullWeb,
            'thumb_web_path' => $thumbWeb,
            'width' => $w,
            'height' => $h,
        ];
    }

    public function removeFiles(?string $fullWebPath, ?string $thumbWebPath): void
    {
        foreach ([$fullWebPath, $thumbWebPath] as $web) {
            if ($web === null || $web === '') {
                continue;
            }
            $abs = $this->projectDir.'/public'.$web;
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
    }

    private function resizeMaxSide(\GdImage $src, int $maxSide): \GdImage
    {
        $w = \imagesx($src);
        $h = \imagesy($src);
        if ($w <= $maxSide && $h <= $maxSide) {
            return $this->copyImage($src);
        }

        $ratio = min($maxSide / $w, $maxSide / $h);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = \imagecreatetruecolor($nw, $nh);
        \imagealphablending($dst, false);
        \imagesavealpha($dst, true);
        $transparent = \imagecolorallocatealpha($dst, 0, 0, 0, 127);
        \imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        \imagealphablending($dst, true);
        \imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    /**
     * JPEG has no alpha; composite onto white so transparent PNG/GIF do not turn black.
     */
    private function flattenAlphaToWhite(\GdImage $src): \GdImage
    {
        $w = \imagesx($src);
        $h = \imagesy($src);
        $dst = \imagecreatetruecolor($w, $h);
        $white = \imagecolorallocate($dst, 255, 255, 255);
        \imagefilledrectangle($dst, 0, 0, $w, $h, $white);
        \imagealphablending($dst, true);
        \imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

        return $dst;
    }

    private function copyImage(\GdImage $src): \GdImage
    {
        $w = \imagesx($src);
        $h = \imagesy($src);
        $dst = \imagecreatetruecolor($w, $h);
        \imagealphablending($dst, false);
        \imagesavealpha($dst, true);
        \imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

        return $dst;
    }
}
