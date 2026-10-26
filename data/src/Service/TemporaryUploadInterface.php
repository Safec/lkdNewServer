<?php

/** @noinspection PhpUnused */

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;

interface TemporaryUploadInterface
{
    public function uploadTemporary(File $file, int $ttl = 300): string;
    public function isTemporary(string $path): bool;
}