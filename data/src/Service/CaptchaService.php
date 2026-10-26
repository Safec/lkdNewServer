<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class CaptchaService
{
    public function __construct(
        private readonly string $hCaptchaSecret,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @param scalar $hCaptchaResponse
     * @throws Throwable
     */
    public function verify(mixed $hCaptchaResponse): bool
    {
        $verificationResponse = $this->httpClient->request(
            Request::METHOD_POST,
            'https://hcaptcha.com/siteverify',
            [
                'body' => [
                    'secret' => $this->hCaptchaSecret,
                    'response' => $hCaptchaResponse,
                ],
            ]
        );

        /** @var mixed $verificationResponse */
        $verificationResponse = json_decode($verificationResponse->getContent(), true);

        return is_array($verificationResponse) && !empty($verificationResponse['success']);
    }
}