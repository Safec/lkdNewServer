<?php

namespace App\Notifier;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

class StreamTelecomTransportFactory extends AbstractTransportFactory
{
    protected function getSupportedSchemes(): array
    {
        return ['streamtelecom'];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if ('streamtelecom' !== $dsn->getScheme()) {
            throw new UnsupportedSchemeException($dsn, 'streamtelecom', $this->getSupportedSchemes());
        }

        $login    = $dsn->getUser();
        $password = $dsn->getPassword();
        $sender   = $dsn->getOption('from', '');

        if (!$login || !$password) {
            throw new \InvalidArgumentException('Stream Telecom DSN must contain login and password.');
        }

        return new StreamTelecomTransport(
            $login,
            $password,
            $sender,
            $this->client,
            $this->dispatcher
        );
    }
}