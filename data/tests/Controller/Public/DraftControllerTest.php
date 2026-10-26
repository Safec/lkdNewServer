<?php

namespace App\Tests\Controller\Public;

use App\Entity\Draft;
use App\Repository\DraftRepository;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DraftControllerTest extends WebTestCase
{
    public function draftsDataProvider(): Generator
    {
        /** @var DraftRepository $draftRepository */
        $draftRepository = self::getContainer()->get(DraftRepository::class);

        foreach ($draftRepository->findAll() as $draft) {
            yield [$draft];
        }
    }

    /**
     * @dataProvider draftsDataProvider
     */
    public function testCanViewDraftWithoutAuth(Draft $draft): void
    {
        $client = self::createClient();
        $client->followRedirects();
        $client->request('GET', '/project/' . $draft->getSlug());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $draft->getTitle());
    }
}
