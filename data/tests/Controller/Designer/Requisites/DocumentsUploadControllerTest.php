<?php

namespace App\Tests\Controller\Designer\Requisites;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Repository\UserRepository;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentsUploadControllerTest extends WebTestCase
{
    private function uploadTestFile(string $prefix = 'test'): UploadedFile
    {
        $filepath = tempnam(sys_get_temp_dir(), $prefix) . '.jpg';
        imagejpeg(imagecreatetruecolor(10, 10), $filepath);

        return new UploadedFile($filepath, $prefix . '.jpg');
    }

    public function designersUploadedFilesDataProvider(): Generator
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        yield [
            $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_SELF_EMPLOYED_EMAIL]),
            [
                'self_employed_documents' => [
                    'passport' => ['files' => [['file' => $this->uploadTestFile('passport'), 'path' => '']]],
                    'INN' => ['files' => [['file' => $this->uploadTestFile('INN'), 'path' => '']]],
                    'SNILS' => ['files' => [['file' => $this->uploadTestFile('SNILS'), 'path' => '']]],
                    'certificate' => ['files' => [['file' => $this->uploadTestFile('certificate'), 'path' => '']]],
                    'bank_account' => ['files' => [['file' => $this->uploadTestFile('bank_account'), 'path' => '']]],
                    'diploma' => ['files' => [['file' => $this->uploadTestFile('diploma'), 'path' => '']]],
                ],
            ]
        ];


        yield [
            $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_INDIVIDUAL_EMAIL]),
            [
                'individual_documents' => [
                    'OGRNIP' => ['files' => [['file' => $this->uploadTestFile('OGRNIP'), 'path' => '']]],
                    'INN' => ['files' => [['file' => $this->uploadTestFile('INN'), 'path' => '']]],
                    'bank_account' => ['files' => [['file' => $this->uploadTestFile('bank_account'), 'path' => '']]],
                    'passport' => ['files' => [['file' => $this->uploadTestFile('passport'), 'path' => '']]],
                    'diploma' => ['files' => [['file' => $this->uploadTestFile('diploma'), 'path' => '']]],
                ],
            ]
        ];

        yield [
            $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_LTD_EMAIL]),
            [
                'company_documents' => [
                    'OGRN' => ['files' => [['file' => $this->uploadTestFile('OGRN'), 'path' => '']]],
                    'INN_KPP' => ['files' => [['file' => $this->uploadTestFile('INN_KPP'), 'path' => '']]],
                    'FNS_USN' => ['files' => [['file' => $this->uploadTestFile('FNS_USN'), 'path' => '']]],
                    'charter' => ['files' => [['file' => $this->uploadTestFile('charter'), 'path' => '']]],
                    'attorney' => ['files' => [['file' => $this->uploadTestFile('attorney'), 'path' => '']]],
                    'bank_account' => ['files' => [['file' => $this->uploadTestFile('bank_account'), 'path' => '']]],
                    'diploma' => ['files' => [['file' => $this->uploadTestFile('diploma'), 'path' => '']]],
                ],
            ]
        ];
    }

    /**
     * @dataProvider designersUploadedFilesDataProvider
     */
    public function testCanUploadDocument(User $designer, array $files): void
    {
        $client = static::createClient();
        $client->loginUser($designer);
        $client->followRedirects();

        $crawler = $client->request('GET', '/designer/requisites/documents-upload');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form($files);
        $crawler = $client->submit($form);

        $crawler->filter('.error')->each(function ($node) {
            $this->fail($node->text());
        });

        $this->assertResponseIsSuccessful();
        $this->assertRouteSame('designer.requisites.complete');

        /** @var DocumentRepository $documentRepository */
        $documentRepository = static::getContainer()->get(DocumentRepository::class);

        foreach ($documentRepository->findBy(['relatedUser' => $designer]) as $document) {
            $documentRepository->remove($document);
        }

        $documentRepository->flush();
    }
}
