<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\Document;
use App\Entity\User;
use App\Repository\UserRepository;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PrivateFilesControllerTest extends WebTestCase
{
    public function managerDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::BOSS_EMAIL])],
            [$userRepository->findOneBy(['email' => UserFixtures::MANAGER_EMAIL])],
        ];
    }

    /**
     * @dataProvider managerDataProvider
     */
    public function testManagerCanDownloadFile(User $manager): void
    {
        $client = static::createClient();
        $client->loginUser($manager);

        $filepath = $client->getKernel()->getProjectDir() . '/uploads/tmp/' . uniqid() . '.jpg';
        register_shutdown_function(function () use ($filepath) {
            if (is_file($filepath)) {
                unlink($filepath);
            }
        });

        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $filepath);

        $client->request('GET', '/private/tmp/' . basename($filepath));
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'image/jpeg');
    }

    public function designerWithDocumentsDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_WITH_PRIVATE_DOCUMENT_EMAIL])],
        ];
    }

    /**
     * @dataProvider designerWithDocumentsDataProvider
     */
    public function testDesignerCanDownloadOwnFile(User $designer): void
    {
        $client = static::createClient();
        $client->loginUser($designer);

        if (!count($designer->getDocuments())) {
            $this->markTestSkipped('У дизайнера нет приватных документов.');
        }

        foreach ($designer->getDocuments() as $document) {
            foreach ($document->getFiles() as $filepath) {
                $client->request('GET', '/private/' . ltrim($filepath, '/'));
                $this->assertResponseIsSuccessful();
                $this->assertResponseHeaderSame('content-type', 'image/jpeg');
            }
        }
    }

    public function designerAndNotOwnsDocumentDataProvider(): Generator
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        $designer = $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_EMAIL]);
        $designerWithDocuments = $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_WITH_PRIVATE_DOCUMENT_EMAIL]);

        foreach ($designerWithDocuments->getDocuments() as $document) {
            yield [$designer, $document];
        }
    }

    /**
     * @dataProvider designerAndNotOwnsDocumentDataProvider
     */
    public function testDesignerCanNotDownloadNotOwnFile(User $designer, Document $notOwnDocument): void
    {
        $client = static::createClient();
        $client->loginUser($designer);

        foreach ($notOwnDocument->getFiles() as $filepath) {
            $client->request('GET', '/private/' . ltrim($filepath, '/'));
            $this->assertEquals(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        }
    }

    /**
     * @dataProvider managerDataProvider
     */
    public function testCanResponseNotFound(User $manager): void
    {
        $client = static::createClient();
        $client->loginUser($manager);
        $client->request('GET', '/private/tmp/undefined.jpg');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
}
