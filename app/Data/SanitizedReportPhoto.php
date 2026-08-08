<?php

namespace App\Data;

final readonly class SanitizedReportPhoto
{
    public function __construct(
        public string $bytes,
        public string $mimeType,
        public string $extension,
        public int $width,
        public int $height,
        public string $sha256,
    ) {}
}
