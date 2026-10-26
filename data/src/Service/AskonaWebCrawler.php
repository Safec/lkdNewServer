<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AskonaWebCrawler
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     *
     * @return array{name: string, price: string, images: string[]}
     */
    public function crawlProductData(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);
        $domain = substr($url, 0, (int)strpos($url, parse_url($url, PHP_URL_PATH)));

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $images = $crawler->filter('.swiper-slide img[src]')->extract(['src']);
        $images = array_map(function ($url) use ($domain) {
            if (!parse_url((string)$url, PHP_URL_SCHEME)) {
                return rtrim($domain, '/') . '/' . ltrim((string)$url, '/');
            } else {
                return (string)$url;
            }
        }, $images);

        return [
            'name' => $crawler->filter('h1')->text(),
            'price' => $crawler->filter('[data-test-card-new-price]')->text(),
            'images' => $images,
        ];
    }
}