<?php

/** @noinspection PhpUnused */

namespace App\MessageHandler;

use App\Message\PublicFileDeleteMessage;
use App\Service\PublicUploadService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler(fromTransport: 'async')]
class PublicFileDeleteMessageHandler
{
    public function __construct(
        private readonly PublicUploadService $publicUploadService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(PublicFileDeleteMessage $publicFileDeleteMessage): void
    {
        try {
            $this->logger->info('Удаление файла {file}.', ['file' => $publicFileDeleteMessage->getPath()]);
            $this->publicUploadService->delete($publicFileDeleteMessage->getPath());
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось удалить файл {file}.', ['file' => $publicFileDeleteMessage->getPath()]);
        }
    }
}