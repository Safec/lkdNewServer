<?php

namespace App\Service;

use App\Message\PrivateFileDeleteMessage;
use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\String\Slugger\SluggerInterface;

class PrivateUploadService implements TemporaryUploadInterface
{
    public function __construct(
        public readonly FilesystemOperator $privateStorage,
        private readonly SluggerInterface $slugger,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws FilesystemException
     */
    public function upload(UploadedFile|File $file, string $dir = 'uploads'): string
    {
        $filename = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        $extension = $file->guessExtension() ?: $file->getExtension();
        $filepath = $this->generateUniqueFilepath($dir, $filename, $extension);

        $this->privateStorage->write($filepath, $file->getContent());

        return $filepath;
    }

    /**
     * @throws FilesystemException
     */
    public function uploadTemporary(File $file, int $ttl = 300): string
    {
        $filepath = $this->upload($file, 'tmp');
        $this->messageBus->dispatch(
            new PrivateFileDeleteMessage($filepath),
            [new DelayStamp($ttl * 1000)]
        );

        return $filepath;
    }

    public function isTemporary(string $path): bool
    {
        return str_starts_with(ltrim($path, '/'), 'tmp/');
    }

    /**
     * @throws FilesystemException
     */
    public function generateUniqueFilepath(string $dir, string $filename, string $extension): string
    {
        $i = 0;
        do {
            $i++;
            if ($i > 10) {
                throw new RuntimeException('Cannot generate unique filename.');
            }

            if ($i == 1) {
                $newFilename = $filename;
            } else {
                try {
                    $uniqueId = preg_replace('/[^a-z0-9]/', '', strtolower(base64_encode(random_bytes(4))));
                } catch (Exception) {
                    $uniqueId = uniqid();
                }

                $newFilename = $filename . '-' . $uniqueId;
            }

            $newFilename = $this->slugger->slug($newFilename)->lower()->toString();

            $subdir = substr(md5(uniqid('', true)), 0, 2);
            $filepath = trim($dir, '/') . '/' . $subdir . '/' . $newFilename . '.' . ltrim($extension, '.');
        } while ($this->privateStorage->has($filepath));

        return $filepath;
    }

    /**
     * @throws FilesystemException
     */
    public function delete(string $filepath): void
    {
        if ($filepath && $this->privateStorage->has($filepath)) {
            $this->privateStorage->delete($filepath);
        }
    }

    /**
     * @throws FilesystemException
     */
    public function move(string $filepath, string $targetDir): string
    {
        $targetFilepath = $this->generateUniqueFilepath(
            $targetDir,
            pathinfo($filepath, PATHINFO_FILENAME),
            pathinfo($filepath, PATHINFO_EXTENSION)
        );

        $this->privateStorage->move($filepath, $targetFilepath);

        return $targetFilepath;
    }
}