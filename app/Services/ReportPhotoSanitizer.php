<?php

namespace App\Services;

use App\Data\SanitizedReportPhoto;
use App\Exceptions\PhotoValidationException;
use finfo;
use GdImage;
use Illuminate\Http\UploadedFile;
use Throwable;

class ReportPhotoSanitizer
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function sanitize(UploadedFile $file): SanitizedReportPhoto
    {
        if (! $file->isValid()) {
            throw $this->invalid('PHOTO_UPLOAD_ERROR', 'The photograph upload was not received correctly.');
        }

        $path = $file->getRealPath();
        $size = $file->getSize();
        $maxBytes = (int) config('report_photos.max_bytes');

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            throw $this->invalid('PHOTO_UPLOAD_ERROR', 'The photograph upload is unavailable.');
        }
        if (! is_int($size) || $size <= 0) {
            throw $this->invalid('PHOTO_EMPTY', 'The photograph is empty.');
        }
        if ($size > $maxBytes) {
            throw $this->invalid('PHOTO_TOO_LARGE', 'The photograph exceeds the allowed size.');
        }

        $bytes = file_get_contents($path, false, null, 0, $maxBytes + 1);
        if (! is_string($bytes) || $bytes === '') {
            throw $this->invalid('PHOTO_READ_FAILED', 'The photograph could not be read.');
        }
        if (strlen($bytes) > $maxBytes) {
            throw $this->invalid('PHOTO_TOO_LARGE', 'The photograph exceeds the allowed size.');
        }

        $actualMime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! is_string($actualMime) || ! in_array($actualMime, self::ALLOWED_MIME_TYPES, true)) {
            throw $this->invalid('PHOTO_UNSUPPORTED_TYPE', 'Only JPEG, PNG, and WebP photographs are supported.');
        }
        $declaredMime = strtolower((string) $file->getClientMimeType());
        if ($declaredMime !== ''
            && $declaredMime !== 'application/octet-stream'
            && $declaredMime !== $actualMime) {
            throw $this->invalid('PHOTO_TYPE_MISMATCH', 'The declared photograph type does not match its content.');
        }

        $dimensions = @getimagesizefromstring($bytes);
        if (! is_array($dimensions) || ! isset($dimensions[0], $dimensions[1], $dimensions['mime'])) {
            throw $this->invalid('PHOTO_DECODE_FAILED', 'The photograph is malformed or cannot be decoded.');
        }
        if ($dimensions['mime'] !== $actualMime) {
            throw $this->invalid('PHOTO_TYPE_MISMATCH', 'The photograph format does not match its decoded content.');
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];
        $this->validateDimensions($width, $height);
        $this->rejectMultiFrameInput($actualMime, $bytes);

        $source = null;
        $canvas = null;

        try {
            $source = @imagecreatefromstring($bytes);
            if (! $source instanceof GdImage) {
                throw $this->invalid('PHOTO_DECODE_FAILED', 'The photograph is malformed or cannot be decoded.');
            }

            $orientation = $actualMime === 'image/jpeg'
                ? $this->readExifOrientation($path)
                : 1;
            $source = $this->applyOrientation($source, $orientation);

            $outputWidth = imagesx($source);
            $outputHeight = imagesy($source);
            $this->validateDimensions($outputWidth, $outputHeight);

            $outputMime = $actualMime === 'image/png' ? 'image/png' : 'image/jpeg';
            $extension = $outputMime === 'image/png' ? 'png' : 'jpg';
            $canvas = $this->newOutputCanvas($outputWidth, $outputHeight, $outputMime);

            if (! imagecopy($canvas, $source, 0, 0, 0, 0, $outputWidth, $outputHeight)) {
                throw $this->invalid('PHOTO_SANITIZE_FAILED', 'The photograph could not be sanitized.');
            }

            $sanitizedBytes = $this->encode($canvas, $outputMime);
            $sanitizedDimensions = @getimagesizefromstring($sanitizedBytes);
            if (! is_array($sanitizedDimensions)
                || ($sanitizedDimensions['mime'] ?? null) !== $outputMime) {
                throw $this->invalid('PHOTO_SANITIZE_FAILED', 'The sanitized photograph could not be verified.');
            }

            return new SanitizedReportPhoto(
                bytes: $sanitizedBytes,
                mimeType: $outputMime,
                extension: $extension,
                width: (int) $sanitizedDimensions[0],
                height: (int) $sanitizedDimensions[1],
                sha256: hash('sha256', $sanitizedBytes),
            );
        } catch (PhotoValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw $this->invalid('PHOTO_SANITIZE_FAILED', 'The photograph could not be sanitized.');
        } finally {
            if ($canvas instanceof GdImage) {
                imagedestroy($canvas);
            }
            if ($source instanceof GdImage) {
                imagedestroy($source);
            }
        }
    }

    private function validateDimensions(int $width, int $height): void
    {
        $maxWidth = (int) config('report_photos.max_width');
        $maxHeight = (int) config('report_photos.max_height');
        $maxPixels = (int) config('report_photos.max_pixels');

        if ($width <= 0 || $height <= 0) {
            throw $this->invalid('PHOTO_INVALID_DIMENSIONS', 'The photograph has invalid dimensions.');
        }
        if ($width > $maxWidth || $height > $maxHeight || $width * $height > $maxPixels) {
            throw $this->invalid('PHOTO_DIMENSIONS_EXCEEDED', 'The photograph dimensions are too large.');
        }
    }

    private function rejectMultiFrameInput(string $mimeType, string $bytes): void
    {
        $animated = match ($mimeType) {
            'image/png' => $this->pngContainsChunk($bytes, 'acTL'),
            'image/webp' => $this->isAnimatedWebp($bytes),
            default => false,
        };

        if ($animated) {
            throw $this->invalid(
                'PHOTO_MULTIFRAME_UNSUPPORTED',
                'Animated or multi-frame photographs are not supported.'
            );
        }
    }

    private function pngContainsChunk(string $bytes, string $target): bool
    {
        if (! str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return false;
        }

        $offset = 8;
        $length = strlen($bytes);
        while ($offset + 12 <= $length) {
            $chunkLength = unpack('Nlength', substr($bytes, $offset, 4))['length'];
            $chunkType = substr($bytes, $offset + 4, 4);
            $next = $offset + 12 + $chunkLength;
            if ($next > $length) {
                return false;
            }
            if ($chunkType === $target) {
                return true;
            }
            if ($chunkType === 'IEND') {
                return false;
            }
            $offset = $next;
        }

        return false;
    }

    private function isAnimatedWebp(string $bytes): bool
    {
        if (strlen($bytes) < 16
            || substr($bytes, 0, 4) !== 'RIFF'
            || substr($bytes, 8, 4) !== 'WEBP') {
            return false;
        }

        $offset = 12;
        $length = strlen($bytes);
        while ($offset + 8 <= $length) {
            $chunkType = substr($bytes, $offset, 4);
            $chunkLength = unpack('Vlength', substr($bytes, $offset + 4, 4))['length'];
            $dataOffset = $offset + 8;
            if ($dataOffset + $chunkLength > $length) {
                return false;
            }
            if ($chunkType === 'ANIM' || $chunkType === 'ANMF') {
                return true;
            }
            if ($chunkType === 'VP8X'
                && $chunkLength >= 1
                && (ord($bytes[$dataOffset]) & 0x02) !== 0) {
                return true;
            }
            $offset = $dataOffset + $chunkLength + ($chunkLength % 2);
        }

        return false;
    }

    private function readExifOrientation(string $path): int
    {
        $data = @exif_read_data($path, 'IFD0', true, false);
        $orientation = $data['IFD0']['Orientation'] ?? $data['Orientation'] ?? 1;
        $orientation = (int) $orientation;

        return $orientation >= 1 && $orientation <= 8 ? $orientation : 1;
    }

    private function applyOrientation(GdImage $image, int $orientation): GdImage
    {
        return match ($orientation) {
            2 => $this->flip($image, IMG_FLIP_HORIZONTAL),
            3 => $this->rotate($image, 180),
            4 => $this->flip($image, IMG_FLIP_VERTICAL),
            5 => $this->rotate($this->flip($image, IMG_FLIP_HORIZONTAL), 90),
            6 => $this->rotate($image, -90),
            7 => $this->rotate($this->flip($image, IMG_FLIP_HORIZONTAL), -90),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    private function flip(GdImage $image, int $mode): GdImage
    {
        if (! imageflip($image, $mode)) {
            throw $this->invalid('PHOTO_ORIENTATION_FAILED', 'The photograph orientation could not be corrected.');
        }

        return $image;
    }

    private function rotate(GdImage $image, int $angle): GdImage
    {
        $rotated = imagerotate($image, $angle, 0);
        if (! $rotated instanceof GdImage) {
            throw $this->invalid('PHOTO_ORIENTATION_FAILED', 'The photograph orientation could not be corrected.');
        }

        imagedestroy($image);

        return $rotated;
    }

    private function newOutputCanvas(int $width, int $height, string $mimeType): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        if (! $canvas instanceof GdImage) {
            throw $this->invalid('PHOTO_SANITIZE_FAILED', 'The photograph could not be sanitized.');
        }

        if ($mimeType === 'image/png') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
        } else {
            [$red, $green, $blue] = config('report_photos.jpeg_background', [255, 255, 255]);
            $background = imagecolorallocate($canvas, (int) $red, (int) $green, (int) $blue);
            imagefill($canvas, 0, 0, $background);
        }

        return $canvas;
    }

    private function encode(GdImage $image, string $mimeType): string
    {
        ob_start();
        try {
            $encoded = $mimeType === 'image/png'
                ? imagepng($image, null, (int) config('report_photos.png_compression'))
                : imagejpeg($image, null, (int) config('report_photos.jpeg_quality'));
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        if (! $encoded || ! is_string($bytes) || $bytes === '') {
            throw $this->invalid('PHOTO_SANITIZE_FAILED', 'The photograph could not be sanitized.');
        }

        return $bytes;
    }

    private function invalid(string $code, string $message): PhotoValidationException
    {
        return new PhotoValidationException($code, $message);
    }
}
