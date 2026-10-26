<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ImagesControllerTest extends WebTestCase
{
    public function testCanResponseOk(): void
    {
        $client = static::createClient();

        $filepath = $client->getKernel()->getProjectDir() . '/public/uploads/tmp/' . uniqid() . '.jpg';
        register_shutdown_function(function () use ($filepath) {
            if (is_file($filepath)) {
                unlink($filepath);
            }
        });

        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $filepath);

        $client->request('GET', '/i/tmp/' . basename($filepath));
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'image/jpeg');
    }

    public function testCanResponseNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/i/tmp/undefined.jpg');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
}
