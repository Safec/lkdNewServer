<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function managerDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['phone' => UserFixtures::BOSS_PHONE])],
            [$userRepository->findOneBy(['phone' => UserFixtures::MANAGER_PHONE])],
        ];
    }

    /**
     * @dataProvider managerDataProvider
     */
    public function testCanRedirectManagersToAdmin(User $manager): void
    {
        $client = static::createClient();
        $client->loginUser($manager);
        $client->request('GET', '/');

        $this->assertResponseRedirects('/admin');
    }

    public function designerDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['phone' => UserFixtures::DESIGNER_PHONE])],
            [$userRepository->findOneBy(['phone' => UserFixtures::NEW_DESIGNER_PHONE])],
        ];
    }

    /**
     * @dataProvider designerDataProvider
     */
    public function testCanRedirectDesignerToDashboard(User $designer): void
    {
        $client = static::createClient();
        $client->loginUser($designer);
        $client->request('GET', '/');

        $this->assertResponseRedirects('/designer');
    }

    public function userWithUnexpectedRoleDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['phone' => UserFixtures::UNEXPECTED_USER_PHONE])],
        ];
    }

    /**
     * @dataProvider userWithUnexpectedRoleDataProvider
     */
    public function testAccessDeniedForUserWithUnexpectedRole(User $user): void
    {
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(403);
    }
}
