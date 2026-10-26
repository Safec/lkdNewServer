<?php

namespace App\Tests\Controller\Designer;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RequisitesControllerTest extends WebTestCase
{
    public function designerFullyRegisteredDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_FULLY_REGISTERED_EMAIL])],
        ];
    }

    /**
     * @dataProvider designerFullyRegisteredDataProvider
     */
    public function testCanRedirectToComplete(User $designer): void
    {
        $client = self::createClient();
        $client->loginUser($designer);
        $client->request('GET', '/designer/requisites');
        $this->assertResponseRedirects('/designer/requisites/complete');
    }
}
