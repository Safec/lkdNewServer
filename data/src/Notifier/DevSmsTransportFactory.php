<?php

/** @noinspection PhpUnused */

namespace App\Notifier;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DevSmsTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        private readonly NotifierInterface $notifier,
        EventDispatcherInterface $dispatcher = null,
        HttpClientInterface $client = null
    ) {
        parent::__construct($dispatcher, $client);
    }

    protected function getSupportedSchemes(): array
    {
        return ['dev'];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if ($dsn->getScheme() !== 'dev') {
            throw new UnsupportedSchemeException($dsn, 'dev', $this->getSupportedSchemes());
        }

        $channel = $dsn->getHost() . (string)$dsn->getPath();

        return new DevSmsTransport($channel, $this->notifier, $this->client, $this->dispatcher);
    }
}