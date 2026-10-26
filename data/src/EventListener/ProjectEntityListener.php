<?php

/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnused */

namespace App\EventListener;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Service\PublicUploadService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Throwable;

#[AsEntityListener(entity: Project::class)]
class ProjectEntityListener
{
    public function __construct(
        private readonly PublicUploadService $publicUploadService,
        private readonly ProjectRepository $projectRepository,
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function prePersist(Project $project, PrePersistEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события prePersist для проекта "{title}".', ['title' => $project->getTitle()]);

            if (!$project->getSlug()) {
                $this->updateSlug($project);
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие prePersist для проекта "{title}".', ['title' => $project->getTitle()]);
        }
    }

    public function preUpdate(Project $project, PreUpdateEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события preUpdate для проекта "{title}".', ['title' => $project->getTitle()]);

            // Удалить файлы старых изображений.
            if ($event->hasChangedField('images')) {
                /** @var array $oldImages */
                $oldImages = is_array($event->getOldValue('images')) ? $event->getOldValue('images') : [];
                /** @var array $newImages */
                $newImages = is_array($event->getNewValue('images')) ? $event->getNewValue('images') : [];

                /**
                 * @var string|mixed $oldImage
                 * @noinspection DuplicatedCode
                 */
                foreach ($oldImages as $oldImage) {
                    if (!in_array($oldImage, $newImages) && is_string($oldImage)) {
                        try {
                            $this->logger->info('Удаляется изображение {image}.', ['image' => $oldImage]);
                            $this->publicUploadService->delete($oldImage);
                        } catch (FilesystemException|Exception $error) {
                            $this->logger->error($error->getMessage() ?: get_class($error));
                            $this->logger->error('Не удалось удалить изображение {image}.', ['image' => $oldImage]);
                        }
                    }
                }
            }

            // Сгенерировать slug.
            if ($event->hasChangedField('title')) {
                try {
                    $this->logger->info('Генерируется slug для проекта "{project}".', ['project' => $event->getNewValue('title')]);
                    $project->setSlug($this->generateUniqueSlug((string)$event->getNewValue('title')));
                } catch (Throwable $error) {
                    $this->logger->error($error->getMessage() ?: get_class($error));
                    $this->logger->error('Не удалось сгенерировать slug для проекта "{project}".', ['project' => $event->getNewValue('title')]);
                }
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие preUpdate для проекта "{title}".', ['title' => $project->getTitle()]);
        }
    }

    public function preFlush(Project $project, PreFlushEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события preFlush для проекта "{title}".', ['title' => $project->getTitle()]);

            if (!$project->getSlug()) {
                $this->updateSlug($project);
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие preFlush для проекта "{title}".', ['title' => $project->getTitle()]);
        }
    }

    public function postRemove(Project $project, PostRemoveEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события postRemove для проекта "{title}".', ['title' => $project->getTitle()]);

            /** @var string|mixed $image */
            foreach ($project->getImages() as $image) {
                if (is_string($image)) {
                    try {
                        $this->logger->info('Удаляется изображение {image}.', ['image' => $image]);
                        $this->publicUploadService->delete($image);
                    } catch (FilesystemException|Exception $error) {
                        $this->logger->error($error->getMessage() ?: get_class($error));
                        $this->logger->error('Не удалось удалить изображение {image}.', ['image' => $image]);
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие postRemove для проекта "{title}".', ['title' => $project->getTitle()]);
        }
    }

    /**
     * @throws Exception
     * @noinspection DuplicatedCode
     */
    private function generateUniqueSlug(string $title): string
    {
        $maxIterations = 10;

        $i = 0;
        do {
            $i++;
            if ($i > $maxIterations) {
                throw new Exception('Too many iterations.');
            }

            $slug = $this->slugger->slug($title)->lower()->toString();
            if (!strlen($slug)) {
                $slug = uniqid();
            }

            $slug = substr($slug, 0, 64);
            if ($i > 1) {
                if ($i == $maxIterations) {
                    $postfix = '-' . uniqid();
                } else {
                    $postfix = '-' . $i;
                }

                $slug = substr($slug, 0, 64 - strlen($postfix)) . $postfix;
            }
        } while ($this->projectRepository->findOneBy(['slug' => $slug]));

        return $slug;
    }

    private function updateSlug(Project $project): void
    {
        try {
            $this->logger->info('Генерируется slug для проекта "{project}".', ['project' => $project->getTitle()]);
            $project->setSlug($this->generateUniqueSlug((string)$project->getTitle()));
        } catch (Throwable $error) {
            $this->logger->error($error->getMessage() ?: get_class($error));
            $this->logger->error(
                'Не удалось сгенерировать slug для проекта "{project}".',
                ['project' => $project->getTitle()]
            );
        }
    }
}