<?php

/** @noinspection PhpUnused */

namespace App\Notifier;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TypeError;

class SmsGatewayTransportFactory extends AbstractTransportFactory
{
    protected function getSupportedSchemes(): array
    {
        return ['smsgateway'];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if ($dsn->getScheme() !== 'smsgateway') {
            throw new UnsupportedSchemeException($dsn, 'smsgateway', $this->getSupportedSchemes());
        }

        $url = 'https://' . $dsn->getHost();
        $bearerToken = $dsn->getPassword(); // Используем пароль как Bearer Token

        if (!$this->client instanceof HttpClientInterface) {
            throw new TypeError(sprintf('Expected an instance of "%s".', HttpClientInterface::class));
        }

        if (!$this->dispatcher instanceof EventDispatcherInterface) {
            throw new TypeError(sprintf('Expected an instance of "%s".', EventDispatcherInterface::class));
        }

        return new SmsGatewayTransport($url, $bearerToken, $this->client, $this->dispatcher);
    }
}