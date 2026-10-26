<?php

namespace App\Service;

use Exception;
use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use SplFileInfo;

class EventService
{
    public function __construct(
        private readonly FilesystemOperator $publicStorage,
        private readonly int $imageWidth = 320,
        private readonly int $imageHeight = 320,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws Exception
     */
    public function save(SplFileInfo $file, string $path): string
    {
        $image = Image::make($file);

        do {
            $filename = preg_replace('/[^a-z0-9]/ui', '', strtolower(base64_encode(random_bytes(16)))) . '.jpg';
            $subdir = substr($filename, 0, 2);
            $filepath = $path . $subdir . '/' . $filename;
        } while ($this->publicStorage->has($filepath));

        //$jpg = $image->fit($this->imageWidth, $this->imageHeight)->encode('jpg');
		$jpg = $image->encode('jpg');
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
    
    public function saveCover(SplFileInfo $file) : string
    {
        $filepath = '/events/cover/';
        return $this->save($file, $filepath);
    }

    public function saveSpeaker(SplFileInfo $file) : string
    {
        $filepath = '/events/speaker/';
        return $this->save($file, $filepath);
    }
}