<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ContractGeneratorService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ContractDownloadControllerTest extends WebTestCase
{
    public function designersWithRequisitesDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_SELF_EMPLOYED_EMAIL])],
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_INDIVIDUAL_EMAIL])],
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_LTD_EMAIL])],
        ];
    }

    /**
     * @dataProvider designersWithRequisitesDataProvider
     */
    public function testCanDesignerDownloadContract(User $designer): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $client->loginUser($designer);
        $client->request('GET', '/designer/requisites/contract/download');

        $this->assertResponseIsSuccessful();
        /** @noinspection SpellCheckingInspection */
        $this->assertResponseHeaderSame('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertResponseHeaderSame('Content-Disposition', 'filename="contract.docx"');
    }

    public function managerAndDesignerDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [
                $userRepository->findOneBy(['email' => UserFixtures::BOSS_EMAIL]),
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_SELF_EMPLOYED_EMAIL]),
            ],
            [
                $userRepository->findOneBy(['email' => UserFixtures::BOSS_EMAIL]),
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_INDIVIDUAL_EMAIL]),
            ],
            [
                $userRepository->findOneBy(['email' => UserFixtures::BOSS_EMAIL]),
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_LTD_EMAIL]),
            ],
            [
                $userRepository->findOneBy(['email' => UserFixtures::MANAGER_EMAIL]),
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_SELF_EMPLOYED_EMAIL]),
            ],
            [
                $userRepository->findOneBy(['email' => UserFixtures::MANAGER_EMAIL]),
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_INDIVIDUAL_EMAIL]),
            ],
            [
                $userRepository->findOneBy(['email' => UserFixtures::MANAGER_EMAIL]),
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_LTD_EMAIL]),
            ],
        ];
    }

    /**
     * @dataProvider managerAndDesignerDataProvider
     */
    public function testCanManagerDownloadContract(User $manager, User $designer): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $client->loginUser($manager);
        $client->request('GET', '/admin/designers/' . $designer->getId() . '/edit/requisites/contract/download');

        $this->assertResponseIsSuccessful();
        /** @noinspection SpellCheckingInspection */
        $this->assertResponseHeaderSame('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertResponseHeaderSame('Content-Disposition', 'filename="contract.docx"');
    }

    public function unexpectedUserDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::UNEXPECTED_USER_EMAIL])],
        ];
    }

    /**
     * @dataProvider unexpectedUserDataProvider
     */
    public function testCanNotUnexpectedUserDownloadContract(User $user): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $client->loginUser($user);
        $client->request('GET', '/designer/requisites/contract/download');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider designersWithRequisitesDataProvider
     */
    public function testNotFoundOnContractGeneratorReturnEmptyContent(User $user): void
    {
        $client = static::createClient();

        $contractGeneratorMock = $this->createMock(ContractGeneratorService::class);
        $contractGeneratorMock->method('generate')->willReturn([]);

        $client->disableReboot();
        $client->getContainer()->set(ContractGeneratorService::class, $contractGeneratorMock);

        $client->loginUser($user);
        $client->request('GET', '/designer/requisites/contract/download');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
