<?php

namespace App\Service;

use App\Message\PublicFileDeleteMessage;
use Exception;
use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Mime\MimeTypes;

class PublicUploadService implements TemporaryUploadInterface
{
    public function __construct(
        private readonly FilesystemOperator $publicStorage,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws Exception
     */
    public function upload(File $file, string $dir = 'uploads'): string
    {
        $extension = $file->guessExtension() ?: $file->getExtension();
        $filepath = $this->generateUniqueFilepath($dir, $extension);
        $this->publicStorage->write($filepath, $file->getContent());

        return $filepath;
    }

    /**
     * @throws FilesystemException
     * @throws Exception
     */
    public function uploadTemporary(File $file, int $ttl = 300): string
    {
        $filepath = $this->upload($file, 'tmp');
        $this->messageBus->dispatch(
            new PublicFileDeleteMessage($filepath),
            [new DelayStamp($ttl * 1000)]
        );

        return $filepath;
    }

    public function isTemporary(string $path): bool
    {
        return str_starts_with(ltrim($path, '/'), 'tmp/');
    }

    /**
     * @throws Exception
     * @throws FilesystemException
     */
    public function saveImage(SplFileInfo|string $content, string $dir = 'images'): string
    {
        $image = Image::make($content);
        $extension = $image->extension ?: MimeTypes::getDefault()->getExtensions($image->mime)[0] ?? null;
        if ($extension === null) {
            throw new Exception('Cannot guess extension.');
        }

        $filepath = $this->generateUniqueFilepath($dir, $extension);
        $this->publicStorage->write($filepath, $image->encode()->getEncoded());

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

    /**
     * @throws FilesystemException
     */
    public function move(string $filepath, string $targetDir = 'images'): string
    {
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $newFilepath = $this->generateUniqueFilepath($targetDir, $extension);
        $this->publicStorage->move($filepath, $newFilepath);

        return $newFilepath;
    }

    /**
     * @throws FilesystemException
     * @throws Exception
     */
    public function generateUniqueFilepath(string $dir, string $extension): string
    {
        do {
            $basename = preg_replace('/[^a-z0-9]/', '', strtolower(base64_encode(random_bytes(16))));
            $subdir = substr($basename, 0, 2);
            $filepath = trim($dir, '/') . '/' . $subdir . '/' . $basename . '.' . ltrim($extension, '.');
        } while ($this->publicStorage->has($filepath));

        return $filepath;
    }
}