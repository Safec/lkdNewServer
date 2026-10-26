<?php

namespace App\Service;

use Exception;
use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use SplFileInfo;

class AvatarService
{
    public function __construct(
        private readonly FilesystemOperator $publicStorage,
        private readonly int $avatarWidth = 320,
        private readonly int $avatarHeight = 320,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws Exception
     */
    public function save(SplFileInfo $file): string
    {
        $image = Image::make($file);

        do {
            $filename = preg_replace('/[^a-z0-9]/ui', '', strtolower(base64_encode(random_bytes(16)))) . '.jpg';
            $subdir = substr($filename, 0, 2);
            $filepath = '/avatars/' . $subdir . '/' . $filename;
        } while ($this->publicStorage->has($filepath));

        $jpg = $image->fit($this->avatarWidth, $this->avatarHeight)->encode('jpg');
        $this->publicStorage->write($filepath, $jpg->getEncoded());

        return $filepath;
    }

    /**
     * @throws FilesystemException
     */
    public function delete(string $filepath): void
    {
        if ($filepath && $this->publicStorage->has($filepath)) {
            $this->publicStorage->delete($filepath);
        }
    }
}