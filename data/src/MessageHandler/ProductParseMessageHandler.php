<?php

/** @noinspection PhpUnused */

namespace App\MessageHandler;

use App\Entity\Product;
use App\Message\ProductParseMessage;
use App\Repository\ProductRepository;
use App\Service\AskonaWebCrawler;
use App\Service\PublicUploadService;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsMessageHandler(fromTransport: 'async')]
class ProductParseMessageHandler
{
    public function __construct(
        private readonly AskonaWebCrawler $crawler,
        private readonly HttpClientInterface $httpClient,
        private readonly PublicUploadService $publicUploadService,
        private readonly ProductRepository $productRepository,
        private readonly LoggerInterface $logger,
        private readonly string $ttl = '24 hours',
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(ProductParseMessage $productParseMessage): void
    {
        try {
            $this->logger->info('Парсинг товара {url}.', ['url' => $productParseMessage->getUrl()]);

            $product = $this->productRepository->findOneBy(['url' => $productParseMessage->getUrl()]) ?? new Product();

            if ($product->getParsedAt() && $product->getParsedAt() > new DateTimeImmutable('-' . $this->ttl)) {
                return;
            }

            $data = $this->crawler->crawlProductData($productParseMessage->getUrl());

            $name = $data['name'] ?? null;
            if (!$name) {
                throw new Exception('Не удалось получить название товара ' . $productParseMessage->getUrl() . '.');
            }

            $imageUrl = $data['images'][0] ?? null;
            if (!$imageUrl) {
                throw new Exception(
                    'Не удалось получить ссылку на изображение товара ' . $productParseMessage->getUrl() . '.'
                );
            }


            $imageDownloadResponse = $this->httpClient->request('GET', $imageUrl);
            if ($imageDownloadResponse->getStatusCode() !== Response::HTTP_OK || !$imageDownloadResponse->getContent(
                )) {
                throw new Exception(
                    'Не удалось скачать изображение ' . $imageUrl . ' товара ' . $productParseMessage->getUrl() . '.'
                );
            }

            $imageFilepath = $this->publicUploadService->saveImage($imageDownloadResponse->getContent(), 'products');
            if (!$imageFilepath) {
                throw new Exception(
                    'Не удалось сохранить изображение ' . $imageUrl . ' товара ' . $productParseMessage->getUrl() . '.'
                );
            }

            $product->setUrl($productParseMessage->getUrl());
            $product->setName($name);
            $product->setImages([$imageFilepath]);
            $product->setParsedAt(new DateTimeImmutable());

            $this->productRepository->save($product)->flush();
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            /** @noinspection SpellCheckingInspection */
            $this->logger->error('Не удалось спарсить товар {url}.', ['url' => $productParseMessage->getUrl()]);
        }
    }
}