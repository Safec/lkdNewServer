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

class SmsGatewayTransport extends AbstractTransport
{
    private string $url;
    private string $bearerToken;

    public function __construct(
        string $url,
        string $bearerToken,
        HttpClientInterface $client,
        EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($client, $dispatcher);
        $this->url = $url;
        $this->bearerToken = $bearerToken;
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

        // Подготавливаем тело запроса в соответствии с новым API
        $requestBody = [
            'destAddr' => $message->getPhone(),
            'message' => [
                'type' => 'SMS',
                'data' => [
                    'text' => $message->getSubject(),
                    'serviceNumber' => $message->getFrom() ?: 'ACKOHA',
                ],
            ],
        ];

        // Убираем вызов getTransportData(), которого нет в SmsMessage
        // Если нужно передать внешний ID, можно использовать опции транспорта

        /**
         * @var ResponseInterface $response
         * @noinspection PhpRedundantVariableDocTypeInspection
         * @psalm-suppress MixedMethodCall
         */
        $response = $this->client->request('POST', $this->url . '/api/v1/sms/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->bearerToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $requestBody,
        ]);

        if ($response->getStatusCode() !== 200) {
            $errorContent = $response->getContent(false);
            $errorMessage = 'Unable to send the SMS';

            try {
                $errorData = json_decode($errorContent, true);
                if (isset($errorData['error'])) {
                    $errorMessage = $errorData['error'];
                }
            } catch (\Exception $e) {
                $errorMessage = $errorContent ?: $errorMessage;
            }

            throw new TransportException(
                sprintf('Unable to send the SMS: %s', $errorMessage),
                $response
            );
        }

        $result = $response->toArray();
        $messageId = $result['gatewayMessageId'] ?? '';

        $sentMsg = new SentMessage($message, (string) $this);
        if ($messageId) {
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
        return sprintf('smsgateway://%s', $this->getEndpoint());
    }
}