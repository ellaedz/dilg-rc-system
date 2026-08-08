<?php

namespace Tests\Unit;

use App\Exceptions\PhotoValidationException;
use App\Services\ReportPhotoSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesTestImages;
use Tests\TestCase;

class ReportPhotoSanitizerTest extends TestCase
{
    use CreatesTestImages;

    #[DataProvider('orientationProvider')]
    public function test_all_exif_orientations_are_applied(
        int $orientation,
        int $expectedWidth,
        int $expectedHeight,
        array $expectedColorOrder,
    ): void {
        $photo = app(ReportPhotoSanitizer::class)->sanitize($this->uploadedImage(
            'oriented.jpg',
            $this->orientationJpegBytes($orientation),
            'image/jpeg'
        ));

        $this->assertSame($expectedWidth, $photo->width);
        $this->assertSame($expectedHeight, $photo->height);
        $this->assertSame('image/jpeg', $photo->mimeType);
        $this->assertStringNotContainsString('Exif', $photo->bytes);
        $this->assertSame(hash('sha256', $photo->bytes), $photo->sha256);
        $this->assertSame($expectedColorOrder, $this->sampleOrientationColors($photo->bytes));
    }

    public static function orientationProvider(): array
    {
        return [
            'normal' => [1, 40, 60, [0, 1, 2, 3, 4, 5]],
            'mirror horizontal' => [2, 40, 60, [1, 0, 3, 2, 5, 4]],
            'rotate 180' => [3, 40, 60, [5, 4, 3, 2, 1, 0]],
            'mirror vertical' => [4, 40, 60, [4, 5, 2, 3, 0, 1]],
            'transpose' => [5, 60, 40, [0, 2, 4, 1, 3, 5]],
            'rotate clockwise' => [6, 60, 40, [4, 2, 0, 5, 3, 1]],
            'transverse' => [7, 60, 40, [5, 3, 1, 4, 2, 0]],
            'rotate counter-clockwise' => [8, 60, 40, [1, 3, 5, 0, 2, 4]],
        ];
    }

    public function test_png_alpha_is_preserved_in_new_png_output(): void
    {
        $original = $this->pngWithTextMetadata(true);
        $photo = app(ReportPhotoSanitizer::class)->sanitize(
            $this->uploadedImage('evidence.png', $original, 'image/png')
        );

        $this->assertSame('image/png', $photo->mimeType);
        $this->assertSame('png', $photo->extension);
        $this->assertNotSame($original, $photo->bytes);
        $this->assertStringNotContainsString('sensitive-metadata', $photo->bytes);
        $decoded = imagecreatefromstring($photo->bytes);
        $alpha = (imagecolorat($decoded, 0, 0) >> 24) & 0x7F;
        imagedestroy($decoded);
        $this->assertGreaterThan(0, $alpha);
    }

    public function test_webp_is_decoded_and_sanitized_to_jpeg(): void
    {
        $photo = app(ReportPhotoSanitizer::class)->sanitize(
            $this->uploadedImage('evidence.webp', $this->webpBytes(), 'image/webp')
        );

        $this->assertSame('image/jpeg', $photo->mimeType);
        $this->assertSame('jpg', $photo->extension);
        $this->assertSame('image/jpeg', getimagesizefromstring($photo->bytes)['mime']);
    }

    public function test_declared_mime_mismatch_is_rejected(): void
    {
        $this->assertPhotoError('PHOTO_TYPE_MISMATCH', fn () => app(
            ReportPhotoSanitizer::class
        )->sanitize($this->uploadedImage('renamed.png', $this->jpegBytes(), 'image/png')));
    }

    public function test_malformed_and_renamed_non_image_are_rejected(): void
    {
        $this->assertPhotoError('PHOTO_UNSUPPORTED_TYPE', fn () => app(
            ReportPhotoSanitizer::class
        )->sanitize($this->uploadedImage(
            'fake.jpg',
            '<?php echo "not an image";',
            'image/jpeg'
        )));
    }

    public function test_dimension_and_byte_limits_are_enforced(): void
    {
        config()->set('report_photos.max_width', 2);
        $this->assertPhotoError('PHOTO_DIMENSIONS_EXCEEDED', fn () => app(
            ReportPhotoSanitizer::class
        )->sanitize($this->uploadedImage(
            'wide.png',
            $this->pngBytes(3, 2),
            'image/png'
        )));
    }

    public function test_animated_png_is_rejected(): void
    {
        $this->assertPhotoError('PHOTO_MULTIFRAME_UNSUPPORTED', fn () => app(
            ReportPhotoSanitizer::class
        )->sanitize($this->uploadedImage(
            'animated.png',
            $this->animatedPngBytes(),
            'image/png'
        )));
    }

    public function test_animated_webp_is_rejected(): void
    {
        $this->assertPhotoError('PHOTO_MULTIFRAME_UNSUPPORTED', fn () => app(
            ReportPhotoSanitizer::class
        )->sanitize($this->uploadedImage(
            'animated.webp',
            $this->animatedWebpBytes(),
            'image/webp'
        )));
    }

    private function assertPhotoError(string $code, callable $callback): void
    {
        try {
            $callback();
        } catch (PhotoValidationException $exception) {
            $this->assertSame($code, $exception->errorCode);

            return;
        }

        $this->fail("Expected photo validation error {$code}.");
    }

    private function sampleOrientationColors(string $bytes): array
    {
        $palette = [
            [240, 20, 20],
            [20, 220, 20],
            [20, 20, 240],
            [240, 220, 20],
            [230, 20, 220],
            [20, 220, 220],
        ];
        $image = imagecreatefromstring($bytes);
        $columns = imagesx($image) / 20;
        $rows = imagesy($image) / 20;
        $result = [];

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                $rgb = imagecolorat($image, $column * 20 + 10, $row * 20 + 10);
                $actual = [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
                $distances = array_map(
                    fn (array $expected): int => array_sum(array_map(
                        fn (int $left, int $right): int => ($left - $right) ** 2,
                        $actual,
                        $expected
                    )),
                    $palette
                );
                $result[] = array_search(min($distances), $distances, true);
            }
        }
        imagedestroy($image);

        return $result;
    }
}
