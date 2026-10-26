<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests;

use App\Service\AskonaWebCrawler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AskonaWebCrawlerTest extends KernelTestCase
{
    public function pageUrlDataProvider(): array
    {
        return [
            ['https://www.askona.ru/matrasy/askona-benefit.htm'],
            ['https://www.askona.ru/matrasy/comfort-plus.htm?SELECTED_HASH_SIZE=80x190-f91bf639f22c0c7123f070bdc86bc66b'],
        ];
    }

    /**
     * @dataProvider pageUrlDataProvider
     */
    public function testCanCrawlProductData($pageUrl): void
    {
        $kernel = self::bootKernel();

        $askonaWebCrawler = $kernel->getContainer()->get(AskonaWebCrawler::class);
        $productData = $askonaWebCrawler->crawlProductData($pageUrl);

        $this->assertIsArray($productData);
        $this->assertIsString($productData['name']);
        $this->assertIsArray($productData['images']);
        foreach ($productData['images'] as $image) {
            $this->assertIsString($image);
            $this->assertStringContainsString('http', $image);
        }
    }
}
