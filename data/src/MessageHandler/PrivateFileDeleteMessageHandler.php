<?php

/** @noinspection PhpUnused */

namespace App\MessageHandler;

use App\Message\PrivateFileDeleteMessage;
use App\Service\PrivateUploadService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler(fromTransport: 'async')]
class PrivateFileDeleteMessageHandler
{
    public function __construct(
        private readonly PrivateUploadService $privateUploadService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(PrivateFileDeleteMessage $privateFileDeleteMessage): void
    {
        try {
            $this->logger->info('Удаление файла {file}.', ['file' => $privateFileDeleteMessage->getPath()]);
            $this->privateUploadService->delete($privateFileDeleteMessage->getPath());
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось удалить файл {file}.', ['file' => $privateFileDeleteMessage->getPath()]);
            throw $e;
        }
    }
}