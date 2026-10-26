<?php

namespace App\Service;

use App\Entity\Draft;
use App\Repository\DraftRepository;
use Exception;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DraftSaveService
{
    public function __construct(
        private readonly DraftRepository $draftRepository,
        private readonly PublicUploadService $publicUploadService,
    ) {
    }

    public function save(Draft $draft): self
    {
        $draft->setImages(array_filter(array_map(function ($image) use ($draft) {
            $isCover = $draft->getCover() === (is_array($image) && isset($image['path']) ? $image['path'] : $image);

            if (is_array($image)) {
                /** @var UploadedFile|string|null $image */
                $image = ($image['file'] ?? null) ?: ($image['path'] ?? null);
            }

            if ($image instanceof UploadedFile) {
                try {
                    $newImage = $this->publicUploadService->upload($image, 'drafts');
                } catch (FilesystemException|Exception) {
                    return null;
                }
            } elseif (is_string($image) && $this->publicUploadService->isTemporary($image)) {
                try {
                    $newImage = $this->publicUploadService->move($image, 'drafts');
                } catch (FilesystemException|Exception) {
                    return null;
                }
            } else {
                /** @var string|null $newImage */
                $newImage = $image;
            }

            if ($isCover) {
                $draft->setCover((string)$newImage ?: null);
            }

            return $newImage;
        }, $draft->getImages())));

        if (!in_array($draft->getCover(), $draft->getImages())) {
            $draft->setCover('');
        }

        $draft->setProducts(array_filter(array_map(function ($product) {
            $urlInfo = parse_url((string)$product);
            if (!empty($urlInfo['host']) && !empty($urlInfo['path'])) {
                return 'https://' . $urlInfo['host'] . $urlInfo['path'];
            } else {
                return null;
            }
        }, $draft->getProducts())));

        $this->draftRepository->save($draft);

        return $this;
    }

    public function flush(): self
    {
        $this->draftRepository->flush();

        return $this;
    }
}