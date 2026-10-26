<?php

namespace App\Tests\Controller\Designer;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PortfolioControllerTest extends WebTestCase
{
    public function designersWithProjectsDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_EMAIL])],
        ];
    }

    /**
     * @dataProvider designersWithProjectsDataProvider
     */
    public function testCanViewPortfolio(User $designer): void
    {
        $client = static::createClient();
        $client->loginUser($designer);
        $client->request('GET', '/designer/portfolio');
        $this->assertResponseIsSuccessful();
        foreach ($designer->getProjects() as $project) {
            $this->assertSelectorTextContains('.projects-list', $project->getTitle());
        }
    }
}
