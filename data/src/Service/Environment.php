<?php

namespace App\Service;

class Environment
{
    public static function getPostMaxSize(): int
    {
        $option = ini_get('post_max_size');
        $value = (int)substr($option, 0, -1);
        $unit = strtoupper(substr($option, -1));

        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => $value,
        };
    }

    public static function getUploadMaxFilesize(): int
    {
        $option = ini_get('upload_max_filesize');
        $value = (int)substr($option, 0, -1);
        $unit = strtoupper(substr($option, -1));

        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => $value,
        };
    }

    public static function getMaxFileSize(): int
    {
        return min(self::getPostMaxSize(), self::getUploadMaxFilesize());
    }

    public static function getMaxFileSizeMb(): float
    {
        return min(self::getPostMaxSize(), self::getUploadMaxFilesize()) / (1024 * 1024);
    }

    public static function getMaxFilesUploads(): int
    {
        return (int)ini_get('max_file_uploads');
    }

    /**
     * @return string[]
     */
    public static function getImageSupportedMimeTypes(): array
    {
        $gd = gd_info();

        return array_values(array_filter([
            ($gd['JPEG Support'] ?? false) ? 'image/jpeg' : '',
            ($gd['PNG Support'] ?? false) ? 'image/png' : '',
            ($gd['GIF Read Support'] ?? false) ? 'image/gif' : '',
            ($gd['WebP Support'] ?? false) ? 'image/webp' : '',
        ]));
    }

    /**
     * @return string[]
     */
    public static function getImageSupportedExtensions(): array
    {
        $gd = gd_info();

        return array_values(array_filter([
            ($gd['JPEG Support'] ?? false) ? 'jpg' : '',
            ($gd['PNG Support'] ?? false) ? 'png' : '',
            ($gd['GIF Read Support'] ?? false) ? 'gif' : '',
            ($gd['WebP Support'] ?? false) ? 'webp' : '',
        ]));
    }
}