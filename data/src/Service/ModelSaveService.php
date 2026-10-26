<?php

namespace App\Service;

use App\Entity\Project;
use App\Repository\ModelRepository;
use App\Entity\Model;
use App\Entity\Document;
use Exception;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ModelSaveService
{
    public function __construct(
        private readonly ModelRepository $modelRepository,
        private readonly PublicUploadService $publicUploadService,
    ) {
    }

    public function saveDocument(Document $document): self
    {
        $document->setFiles(array_map(function ($file) {
            if (is_array($file)) {
                /** @var File|string|null $file */
                $file = ($file['file'] ?? null) ?: ($file['path'] ?? null);
            }

            if ($file instanceof File) {
                try {
                    return $this->publicUploadService->upload($file, 'models_docs');
                } catch (FilesystemException) {
                    return null;
                }
            } elseif (is_string($file) && $this->publicUploadService->isTemporary($file)) {
                try {
                    return $this->publicUploadService->move($file, 'models_docs');
                } catch (FilesystemException) {
                    return null;
                }
            } else {
                return $file;
            }
        }, $document->getFiles()));

        //$this->modelRepository->save($document);

        return $this;
    }

    public function saveImages(Model $model): self
    {
        $model->setImages(array_filter(array_map(function ($image) use ($model) {
            $isCover = $model->getCover() === (is_array($image) && isset($image['path']) ? $image['path'] : $image);

            if (is_array($image)) {
                /** @var UploadedFile|string|null $image */
                $image = ($image['file'] ?? null) ?: ($image['path'] ?? null);
            }

            if ($image instanceof UploadedFile) {
                try {
                    $newImage = $this->publicUploadService->upload($image, 'models');
                } catch (FilesystemException|Exception) {
                    return null;
                }
            } elseif (is_string($image) && $this->publicUploadService->isTemporary($image)) {
                try {
                    $newImage = $this->publicUploadService->move($image, 'models');
                } catch (FilesystemException|Exception) {
                    return null;
                }
            } else {
                /** @var string|null $newImage */
                $newImage = $image;
            }

            if ($isCover) {
                $model->setCover((string)$newImage ?: null);
            }

            return $newImage;
        }, $model->getImages())));

        if (!in_array($model->getCover(), $model->getImages())) {
            $model->setCover('');
        }

        $this->modelRepository->save($model);

        return $this;
    }

    public function flush(): self
    {
        $this->modelRepository->flush();

        return $this;
    }

    public function removeFiles(Model $entity): self
    {
        // Получение списка файлов из поля images
        $images = $entity->getImages();

        // Пройтись по каждому файлу и удалить его с файловой системы
        foreach ($images as $imagePath) {
                $this->publicUploadService->delete($imagePath);
        }

        // Получение списка файлов из поля images
        $files = $entity->getPreviewImage();

        // Пройтись по каждому файлу и удалить его с файловой системы
        foreach ($files as $filePath) {
                $this->publicUploadService->delete($filePath);
        }

        return $this;
    }

    public function save(Model $entity): self
    {
        $this->modelRepository->save($entity);

        return $this;
    }

    /** @noinspection PhpUnused */
    public function remove(Model $entity): self
    {
        $this->modelRepository()->remove($entity);

        return $this;
    }
}