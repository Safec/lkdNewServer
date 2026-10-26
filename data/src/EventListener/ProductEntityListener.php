<?php

/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnused */

namespace App\EventListener;

use App\Entity\Product;
use App\Service\PublicUploadService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Throwable;

#[AsEntityListener(entity: Product::class)]
class ProductEntityListener
{
    public function __construct(
        private readonly PublicUploadService $publicUploadService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function preUpdate(Product $product, PreUpdateEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события preUpdate для товара "{name}".', ['name' => $product->getName()]);

            /** @noinspection DuplicatedCode */
            if ($event->hasChangedField('images')) {
                /** @var array $oldImages */
                $oldImages = is_array($event->getOldValue('images')) ? $event->getOldValue('images') : [];
                /** @var array $newImages */
                $newImages = is_array($event->getNewValue('images')) ? $event->getNewValue('images') : [];
                /** @var string|mixed $oldImage */
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
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие preUpdate для товара "{name}".', ['name' => $product->getName()]);
        }
    }

    public function postRemove(Product $product, PostRemoveEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события postRemove для товара "{name}".', ['name' => $product->getName()]);

            /**
             * @var string|mixed $image
             * @noinspection DuplicatedCode
             */
            foreach ($product->getImages() as $image) {
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
            $this->logger->error('Не удалось обработать событие postRemove для товара "{name}".', ['name' => $product->getName()]);
        }
    }
}