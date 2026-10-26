<?php

/** @noinspection PhpUnused */

namespace App\Notifier;

use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RapportoSmsTransport extends AbstractTransport
{
    private string $url;
    private string $serviceId;
    private string $password;

    public function __construct(
        string $url,
        string $serviceId,
        string $password,
        HttpClientInterface $client,
        EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($client, $dispatcher);
        $this->url = $url;
        $this->serviceId = $serviceId;
        $this->password = $password;
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        /**
         * @var ResponseInterface $response
         * @noinspection PhpRedundantVariableDocTypeInspection
         * @psalm-suppress MixedMethodCall
         */
        $response = $this->client->request('POST', $this->url, [
            'body' => [
                'serviceId' => $message->getFrom() ?: $this->serviceId,
                'pass' => $this->password,
                'clientId' => $message->getPhone(),
                'message' => $message->getSubject(),
            ]
        ]);

        if ($response->getStatusCode() != 200) {
            throw new TransportException(
                sprintf('Unable to send the SMS: %s.', $response->getContent() ?: 'unknown error'),
                $response
            );
        }

        $result = explode("\n", $response->getContent());
        $messageId = trim($result[1] ?? '');

        $sentMsg = new SentMessage($message, (string) $this);
        if (strlen($messageId)) {
            $sentMsg->setMessageId($messageId);
        }

        return $sentMsg;
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    public function __toString(): string
    {
        return sprintf('rapportosms://%s@%s', $this->serviceId, $this->getEndpoint());
    }
}