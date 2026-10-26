<?php

namespace App\Service;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Exception;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProjectSaveService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly PublicUploadService $publicUploadService,
    ) {
    }

    public function save(Project $project): self
    {
        $project->setImages(array_filter(array_map(function ($image) use ($project) {
            $isCover = $project->getCover() === (is_array($image) && isset($image['path']) ? $image['path'] : $image);

            if (is_array($image)) {
                /** @var UploadedFile|string|null $image */
                $image = ($image['file'] ?? null) ?: ($image['path'] ?? null);
            }

            if ($image instanceof UploadedFile) {
                try {
                    $newImage = $this->publicUploadService->upload($image, 'projects');
                } catch (FilesystemException|Exception) {
                    return null;
                }
            } elseif (is_string($image) && $this->publicUploadService->isTemporary($image)) {
                try {
                    $newImage = $this->publicUploadService->move($image, 'projects');
                } catch (FilesystemException|Exception) {
                    return null;
                }
            } else {
                /** @var string|null $newImage */
                $newImage = $image;
            }

            if ($isCover) {
                $project->setCover((string)$newImage ?: null);
            }

            return $newImage;
        }, $project->getImages())));

        if (!in_array($project->getCover(), $project->getImages())) {
            $project->setCover('');
        }

        $this->projectRepository->save($project);

        return $this;
    }

    public function flush(): self
    {
        $this->projectRepository->flush();

        return $this;
    }
}