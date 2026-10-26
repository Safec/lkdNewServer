<?php

/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnused */

namespace App\EventListener;

use App\Entity\Document;
use App\Service\PrivateUploadService;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Throwable;

#[AsEntityListener(entity: Document::class)]
class DocumentEntityListener
{
    public function __construct(
        private readonly PrivateUploadService $privateUploadService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function prePersist(Document $document, PrePersistEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события prePersist для документа #{id}.', ['id' => $document->getId()]);

            if ($document->getFiles()) {
                $document->setUploadedAt(new DateTimeImmutable());
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие prePersist для документа #{id}.', ['id' => $document->getId()]);
        }
    }

    public function preUpdate(Document $document, PreUpdateEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события preUpdate для документа #{id}.', ['id' => $document->getId()]);

            if ($event->hasChangedField('files')) {
                /** @var array $oldFiles */
                $oldFiles = is_array($event->getOldValue('files')) ? $event->getOldValue('files') : [];
                /** @var array $newFiles */
                $newFiles = is_array($event->getNewValue('files')) ? $event->getNewValue('files') : [];

                /** @var string|mixed $oldFile */
                foreach ($oldFiles as $oldFile) {
                    if (!in_array($oldFile, $newFiles) && is_string($oldFile)) {
                        try {
                            $this->logger->info('Удаляется файл {file}.', ['file' => $oldFile]);
                            $this->privateUploadService->delete($oldFile);
                        } catch (FilesystemException|Exception $error) {
                            $this->logger->error($error->getMessage() ?: get_class($error));
                            $this->logger->error('Не удалось удалить файл {file}.', ['file' => $oldFile]);
                        }
                    }
                }

                /** @var string|mixed $newFile */
                foreach ($newFiles as $newFile) {
                    if (!in_array($newFile, $oldFiles)) {
                        $document->setUploadedAt(new DateTimeImmutable());
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие preUpdate для документа #{id}.', ['id' => $document->getId()]);
        }
    }

    public function postRemove(Document $document, PostRemoveEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события postRemove для документа #{id}.', ['id' => $document->getId()]);

            /** @var string|mixed $file */
            foreach ($document->getFiles() as $file) {
                if (is_string($file)) {
                    try {
                        $this->logger->info('Удаляется файл {file}.', ['file' => $file]);
                        $this->privateUploadService->delete($file);
                    } catch (FilesystemException|Exception $error) {
                        $this->logger->error($error->getMessage() ?: get_class($error));
                        $this->logger->error('Не удалось удалить файл {file}.', ['file' => $file]);
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие postRemove для документа #{id}.', ['id' => $document->getId()]);
        }
    }
}