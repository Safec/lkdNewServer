<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\User;
use Dadata\DadataClient;
use DateTime;
use GuzzleHttp\Exception\ClientException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DadataController extends AbstractController
{
    public function __construct(
        private readonly RateLimiterFactory $dadataSuggestLimiter,
        private readonly DadataClient $dadataClient,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route('/ajax/dadata/inn', name: 'ajax.dadata.inn')]
    public function suggest(Request $request): JsonResponse
    {
        if (!$this->isGranted(User::ROLE_MANAGER) && !$this->isGranted(User::ROLE_DESIGNER)) {
            throw $this->createAccessDeniedException();
        }

        $rateLimiter = $this->dadataSuggestLimiter->create($request->getClientIp() ?? '')->consume();
        if (!$rateLimiter->isAccepted()) {
            return $this->json(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            if ($request->getContentTypeFormat() === 'json') {
                /** @var array|mixed $data */
                $data = json_decode($request->getContent(), true);
                /** @var mixed $inn */
                $inn = is_array($data) ? ($data['INN'] ?? $data['inn'] ?? '') : '';
                $inn = is_string($inn) || is_numeric($inn) ? strval($inn) : '';
            } else {
                $inn = strval($request->request->get('INN', $request->query->get('INN', '')));
            }

            if (!strlen($inn)) {
                return $this->json(['error' => 'Empty INN'], Response::HTTP_BAD_REQUEST);
            }

            /** @var array $data */
            $data = $this->cache->get('dadata.inn.' . $inn, function (ItemInterface $cache) use ($inn) {
                $cache->expiresAfter(7 * 24 * 3600);
                /** @var array|mixed $response */
                $response = $this->dadataClient->findById('party', $inn, 1);
                /** @var array<string, mixed> $data */
                $data = is_array($response) && isset($response[0]) && is_array($response[0]) ? $response[0] : [];
                $data = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];
                $data['updated_at'] = (new DateTime())->format('Y-m-d H:i:s');

                return $data;
            });

            if (!empty($data)) {
                return $this->json($data);
            } else {
                return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
            }
        } catch (ClientException $exception) {
            return $this->json(['error' => $exception->getResponse()->getReasonPhrase()], $exception->getCode());
        }
    }
}