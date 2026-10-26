<?php

namespace App\Tests\Controller\Designer;

use App\DataFixtures\UserFixtures;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testCanRedirectToRequisites(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $designer = $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_WITHOUT_REQUISITES_EMAIL]);

        $client->loginUser($designer);
        $client->request('GET', '/designer');
        $this->assertResponseRedirects('/designer/requisites');
    }

    public function testCanRedirectToContacts(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $designer = $userRepository->findOneBy(['phone' => UserFixtures::NEW_DESIGNER_PHONE]);

        $client->loginUser($designer);
        $client->request('GET', '/designer');
        $this->assertResponseRedirects('/designer/contacts');
    }
}
