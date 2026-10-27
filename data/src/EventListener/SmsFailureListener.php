<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Notifier\Event\FailedMessageEvent;

#[AsEventListener]
class SmsFailureListener
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(FailedMessageEvent $event): void
    {
        $this->logger->error('SMS LISTENER CALLED', [
            'subject' => $event->getMessage()->getSubject(),
        ]);
        $this->logger->error('SMS FAILED', [
            'subject' => $event->getMessage()->getSubject(),
            'error' => $event->getError()->getMessage(),
        ]);
    }
}