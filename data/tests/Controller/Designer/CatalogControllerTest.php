<?php

namespace App\Tests\Controller\Designer;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CatalogControllerTest extends WebTestCase
{
    public function activeDesignersDataProfile(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_EMAIL])],
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_FULLY_REGISTERED_EMAIL])],
        ];
    }

    /**
     * @dataProvider activeDesignersDataProfile
     */
    public function testCanResponseOk(User $activeDesigner): void
    {
        $client = static::createClient();
        $client->loginUser($activeDesigner);
        $client->request('GET', '/designer/catalog');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.main-placeholder p', '3D модель');
    }
}
