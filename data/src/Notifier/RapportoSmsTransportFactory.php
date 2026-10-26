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

class RapportoSmsTransportFactory extends AbstractTransportFactory
{
    protected function getSupportedSchemes(): array
    {
        return ['rapportosms'];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if ($dsn->getScheme() !== 'rapportosms') {
            throw new UnsupportedSchemeException($dsn, 'rapportosms', $this->getSupportedSchemes());
        }

        $url = 'https://' . $dsn->getHost() . (string)$dsn->getPath();
        $serviceId = (string)$dsn->getUser();
        $password = (string)$dsn->getPassword();

        if (!$this->client instanceof HttpClientInterface) {
            throw new TypeError(sprintf('Expected an instance of "%s".', HttpClientInterface::class));
        }

        if (!$this->dispatcher instanceof EventDispatcherInterface) {
            throw new TypeError(sprintf('Expected an instance of "%s".', EventDispatcherInterface::class));
        }

        return new RapportoSmsTransport($url, $serviceId, $password, $this->client, $this->dispatcher);
    }
}