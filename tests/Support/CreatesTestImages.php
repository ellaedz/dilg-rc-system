<?php

namespace Tests\Support;

use GdImage;
use Illuminate\Http\UploadedFile;

trait CreatesTestImages
{
    protected function jpegBytes(int $width = 4, int $height = 3): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 30, 90, 180);
        imagefill($image, 0, 0, $background);

        return $this->encoded($image, fn (GdImage $value): bool => imagejpeg($value, null, 100));
    }

    protected function orientationJpegBytes(int $orientation): string
    {
        $block = 20;
        $image = imagecreatetruecolor(2 * $block, 3 * $block);
        $colors = [
            [240, 20, 20],
            [20, 220, 20],
            [20, 20, 240],
            [240, 220, 20],
            [230, 20, 220],
            [20, 220, 220],
        ];
        foreach ($colors as $index => [$red, $green, $blue]) {
            imagefilledrectangle(
                $image,
                ($index % 2) * $block,
                intdiv($index, 2) * $block,
                (($index % 2) + 1) * $block - 1,
                (intdiv($index, 2) + 1) * $block - 1,
                imagecolorallocate($image, $red, $green, $blue)
            );
        }
        $jpeg = $this->encoded($image, fn (GdImage $value): bool => imagejpeg($value, null, 100));

        $tiff = 'II'.pack('v', 42).pack('V', 8)
            .pack('v', 1)
            .pack('v', 0x0112).pack('v', 3).pack('V', 1).pack('v', $orientation).pack('v', 0)
            .pack('V', 0);
        $payload = "Exif\0\0".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    protected function pngBytes(
        int $width = 4,
        int $height = 3,
        bool $transparent = false
    ): string {
        $image = imagecreatetruecolor($width, $height);
        if ($transparent) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $background = imagecolorallocatealpha($image, 20, 120, 220, 80);
        } else {
            $background = imagecolorallocate($image, 20, 120, 220);
        }
        imagefill($image, 0, 0, $background);

        return $this->encoded($image, fn (GdImage $value): bool => imagepng($value, null, 6));
    }

    protected function webpBytes(int $width = 4, int $height = 3): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 180, 80, 30);
        imagefill($image, 0, 0, $background);

        return $this->encoded($image, fn (GdImage $value): bool => imagewebp($value, null, 90));
    }

    protected function animatedPngBytes(): string
    {
        $chunkData = pack('NN', 2, 0);

        return $this->insertPngChunk($this->pngBytes(), 'acTL', $chunkData);
    }

    protected function pngWithTextMetadata(bool $transparent = false): string
    {
        return $this->insertPngChunk(
            $this->pngBytes(3, 2, $transparent),
            'tEXt',
            "Comment\0sensitive-metadata"
        );
    }

    protected function animatedWebpBytes(): string
    {
        $webp = $this->webpBytes();
        $chunk = 'ANIM'.pack('V', 6).str_repeat("\0", 6);
        $webp .= $chunk;

        return substr_replace($webp, pack('V', strlen($webp) - 8), 4, 4);
    }

    protected function uploadedImage(
        string $name,
        string $bytes,
        string $declaredMime = 'application/octet-stream'
    ): UploadedFile {
        $path = tempnam(sys_get_temp_dir(), 'civiclear-photo-test-');
        file_put_contents($path, $bytes);

        return new UploadedFile($path, $name, $declaredMime, UPLOAD_ERR_OK, true);
    }

    private function encoded(GdImage $image, callable $encoder): string
    {
        ob_start();
        try {
            $this->assertTrue($encoder($image));
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }

        $this->assertIsString($bytes);

        return $bytes;
    }

    private function insertPngChunk(string $png, string $type, string $data): string
    {
        $chunk = pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $iend = strrpos($png, 'IEND') - 4;

        return substr($png, 0, $iend).$chunk.substr($png, $iend);
    }
}
