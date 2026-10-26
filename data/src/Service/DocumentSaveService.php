<?php

namespace App\Service;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\File;

class DocumentSaveService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly PrivateUploadService $privateUploadService,
    ) {
    }

    public function save(Document $document): self
    {
        $document->setFiles(array_map(function ($file) {
            if (is_array($file)) {
                /** @var File|string|null $file */
                $file = ($file['file'] ?? null) ?: ($file['path'] ?? null);
            }

            if ($file instanceof File) {
                try {
                    return $this->privateUploadService->upload($file, 'documents');
                } catch (FilesystemException) {
                    return null;
                }
            } elseif (is_string($file) && $this->privateUploadService->isTemporary($file)) {
                try {
                    return $this->privateUploadService->move($file, 'documents');
                } catch (FilesystemException) {
                    return null;
                }
            } else {
                return $file;
            }
        }, $document->getFiles()));

        $this->documentRepository->save($document);

        return $this;
    }

    public function flush(): self
    {
        $this->documentRepository->flush();

        return $this;
    }
}