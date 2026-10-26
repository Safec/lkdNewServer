<?php

namespace App\Notifier;

use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StreamTelecomTransport extends AbstractTransport
{
    private const SESSION_URL = 'https://gateway.api.sc/rest/Session/';
    private const SEND_URL    = 'https://gateway.api.sc/rest/Send/SendSms/';

    private string $login;
    private string $password;
    private string $sender;

    private ?string $sessionId = null;

    public function __construct(
        string $login,
        string $password,
        string $sender,
        HttpClientInterface $client,
        EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($client, $dispatcher);
        $this->login = $login;
        $this->password = $password;
        $this->sender = $sender;
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $sessionId = $this->getSessionId();

        $response = $this->client->request('POST', self::SEND_URL, [
            'body' => [
                'sessionId'          => $sessionId,
                'sourceAddress'      => $message->getFrom() ?: $this->sender,
                'destinationAddress' => ltrim($message->getPhone(), '+'),
                'data'               => $message->getSubject(),
                'validity'           => 300,
                'name_deliver'       => 'symfony-notifier',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $content = trim($response->getContent(false));

        if ($statusCode !== 200) {
            throw new TransportException(
                sprintf('Stream Telecom HTTP error %d: %s', $statusCode, $content ?: 'unknown'),
                $response
            );
        }

        // Если сессия устарела — сбрасываем и повторяем один раз
        if ($this->looksLikeSessionError($content)) {
            $this->sessionId = null;
            $sessionId = $this->getSessionId();

            $response = $this->client->request('POST', self::SEND_URL, [
                'body' => [
                    'sessionId'          => $sessionId,
                    'sourceAddress'      => $message->getFrom() ?: $this->sender,
                    'destinationAddress' => ltrim($message->getPhone(), '+'),
                    'data'               => $message->getSubject(),
                    'validity'           => 300,
                    'name_deliver'       => 'symfony-notifier',
                ],
            ]);

            $content = trim($response->getContent(false));
        }

        $data = json_decode($content, true);

        // Stream Telecom может возвращать:
        //   ["3273115194892456610"]           — массив ID
        //   {"messageId": "..."}              — объект
        //   "3273115194892456610"             — строка
        //   3273115194892456610               — plain text
        $messageId = null;
        $error = null;

        if (is_array($data)) {
            // Массив ID (ваш случай)
            if (!empty($data) && array_keys($data) === range(0, count($data) - 1)) {
                $messageId = (string) $data[0];
            } else {
                // Ассоциативный массив
                $messageId = $data['messageId']
                    ?? $data['id']
                    ?? $data['smsId']
                    ?? $data['id_sms']
                    ?? null;

                $error = $data['error'] ?? $data['errorMessage'] ?? $data['message'] ?? null;
            }
        } elseif (is_string($data) && $data !== '') {
            $messageId = $data;
        } elseif (preg_match('/^\d+$/', $content)) {
            $messageId = $content;
        }

        if (!$messageId && $error) {
            throw new TransportException(
                sprintf('Stream Telecom error: %s', is_array($error) ? json_encode($error) : $error),
                $response
            );
        }

        if (!$messageId) {
            throw new TransportException(
                sprintf('Stream Telecom: no message ID in response: %s', $content),
                $response
            );
        }

        $sentMsg = new SentMessage($message, (string) $this);
        $sentMsg->setMessageId((string) $messageId);

        return $sentMsg;
    }

    /**
     * Получает sessionId (кеширует на время жизни объекта).
     */
    private function getSessionId(): string
    {
        if ($this->sessionId !== null) {
            return $this->sessionId;
        }

        $response = $this->client->request('POST', self::SESSION_URL, [
            'body' => [
                'login'    => $this->login,
                'password' => $this->password,
            ],
        ]);

        $content = trim($response->getContent(false));

        if ($response->getStatusCode() !== 200) {
            throw new TransportException(
                sprintf('Stream Telecom auth failed (HTTP %d): %s', $response->getStatusCode(), $content),
                $response
            );
        }

        $sessionId = $this->extractSessionId($content);

        if (!$sessionId) {
            throw new TransportException(
                sprintf('Stream Telecom: no sessionId in response: %s', $content),
                $response
            );
        }

        $this->sessionId = $sessionId;

        return $this->sessionId;
    }

    /**
     * Извлекает sessionId из ответа: может быть JSON-строкой,
     * JSON-объектом или просто текстом.
     */
    private function extractSessionId(string $content): ?string
    {
        // 1. Пробуем JSON (строка в кавычках или объект)
        $data = json_decode($content, true);

        if (is_string($data) && $data !== '') {
            // Ответ вида: "c5170a30ac463795e5b660c71c89e4de"
            return $data;
        }

        if (is_array($data)) {
            $id = $data['sessionId']
                ?? $data['session_id']
                ?? $data['id']
                ?? null;

            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        // 2. Фолбэк: просто текст без кавычек и пробелов
        $clean = trim($content, " \t\n\r\0\x0B\"'");

        if ($clean !== '' && preg_match('/^[A-Za-z0-9\-_]+$/', $clean)) {
            return $clean;
        }

        return null;
    }

    private function looksLikeSessionError(string $content): bool
    {
        $lower = mb_strtolower($content);

        return str_contains($lower, 'session')
            || str_contains($lower, 'auth')
            || str_contains($lower, 'unauthorized');
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    public function __toString(): string
    {
        return sprintf('streamtelecom://%s', $this->getEndpoint());
    }
}