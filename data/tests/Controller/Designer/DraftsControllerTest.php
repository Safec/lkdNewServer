<?php

namespace App\Tests\Controller\Designer;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DraftsControllerTest extends WebTestCase
{
    public function designersWithDraftsDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_EMAIL])],
        ];
    }

    /**
     * @dataProvider designersWithDraftsDataProvider
     */
    public function testCanViewDrafts(User $designer): void
    {
        $client = static::createClient();
        $client->loginUser($designer);

        $client->request('GET', '/designer/drafts');
        $this->assertResponseIsSuccessful();

        foreach ($designer->getDrafts() as $draft) {
            $this->assertSelectorTextContains('.projects-list', $draft->getTitle());
        }
    }
}
