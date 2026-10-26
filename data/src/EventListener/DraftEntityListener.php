<?php

/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnused */

namespace App\EventListener;

use App\Entity\Draft;
use App\Message\ProductParseMessage;
use App\Repository\DraftRepository;
use App\Service\PublicUploadService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Throwable;

#[AsEntityListener(entity: Draft::class)]
class DraftEntityListener
{
    public function __construct(
        private readonly DraftRepository $draftRepository,
        private readonly SluggerInterface $slugger,
        private readonly MessageBusInterface $messageBus,
        private readonly PublicUploadService $publicUploadService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function prePersist(Draft $draft, PrePersistEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события prePersist для черновика "{title}".', ['title' => $draft->getTitle()]);

            if ($draft->getProducts()) {
                /** @var string|mixed $product */
                foreach ($draft->getProducts() as $product) {
                    if (is_string($product)) {
                        $this->messageBus->dispatch(new ProductParseMessage($product));
                    }
                }
            }

            if (!$draft->getSlug()) {
                $this->updateSlug($draft);
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие prePersist для черновика "{title}".', ['title' => $draft->getTitle()]);
        }
    }

    public function preUpdate(Draft $draft, PreUpdateEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события preUpdate для черновика "{title}".', ['title' => $draft->getTitle()]);

            if ($event->hasChangedField('products')) {
                /** @var array $oldProducts */
                $oldProducts = is_array($event->getOldValue('products')) ? $event->getOldValue('products') : [];
                /** @var array $newProducts */
                $newProducts = is_array($event->getNewValue('products')) ? $event->getNewValue('products') : [];
                /** @var string|mixed $newProduct */
                foreach ($newProducts as $newProduct) {
                    if (!in_array($newProduct, $oldProducts) && is_string($newProduct)) {
                        $this->messageBus->dispatch(new ProductParseMessage($newProduct));
                    }
                }
            }

            if ($event->hasChangedField('title')) {
                try {
                    $this->logger->info('Обновляется slug для черновика "{title}".', ['title' => $draft->getTitle()]);
                    $draft->setSlug($this->generateUniqueSlug((string)$event->getNewValue('title')));
                } catch (Throwable $e) {
                    $this->logger->error($e->getMessage() ?: get_class($e));
                    $this->logger->error(
                        'Не удалось обновить slug для черновика "{title}".',
                        ['title' => $draft->getTitle()]
                    );
                }
            }

            if ($event->hasChangedField('images')) {
                /** @var array $oldImages */
                $oldImages = is_array($event->getOldValue('images')) ? $event->getOldValue('images') : [];
                /** @var array $newImages */
                $newImages = is_array($event->getNewValue('images')) ? $event->getNewValue('images') : [];
                /** @var string|mixed $oldImage */
                foreach ($oldImages as $oldImage) {
                    if (!in_array($oldImage, $newImages) && is_string($oldImage)) {
                        try {
                            $this->logger->info('Удаляется изображение "{image}".', ['image' => $oldImage]);
                            $this->publicUploadService->delete($oldImage);
                        } catch (FilesystemException|Throwable $e) {
                            $this->logger->error($e->getMessage() ?: get_class($e));
                            $this->logger->error('Не удалось удалить изображение "{image}".', ['image' => $oldImage]);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие preUpdate для черновика "{title}".', ['title' => $draft->getTitle()]);
        }
    }

    public function preFlush(Draft $draft, PreFlushEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события preFlush для черновика "{title}".', ['title' => $draft->getTitle()]);

            if (!$draft->getSlug()) {
                $this->updateSlug($draft);
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие preFlush для черновика "{title}".', ['title' => $draft->getTitle()]);
        }
    }

    public function postRemove(Draft $draft, PostRemoveEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события postRemove для черновика "{title}".', ['title' => $draft->getTitle()]);

            /** @var string|mixed $image */
            foreach ($draft->getImages() as $image) {
                if (is_string($image)) {
                    try {
                        $this->logger->info('Удаляется изображение "{image}".', ['image' => $image]);
                        $this->publicUploadService->delete($image);
                    } catch (FilesystemException|Throwable $e) {
                        $this->logger->error($e->getMessage() ?: get_class($e));
                        $this->logger->error('Не удалось удалить изображение "{image}".', ['image' => $image]);
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие postRemove для черновика "{title}".', ['title' => $draft->getTitle()]);
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
        } while ($this->draftRepository->findOneBy(['slug' => $slug]));

        return $slug;
    }

    private function updateSlug(Draft $draft): void
    {
        try {
            $this->logger->info('Создаётся slug для черновика "{title}".', ['title' => $draft->getTitle()]);
            $draft->setSlug($this->generateUniqueSlug((string)$draft->getTitle()));
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось создать slug для черновика "{title}".', ['title' => $draft->getTitle()]);
        }
    }
}