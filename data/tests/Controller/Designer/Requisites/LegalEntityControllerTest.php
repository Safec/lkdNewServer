<?php

namespace App\Tests\Controller\Designer\Requisites;

use App\DataFixtures\UserFixtures;
use App\Entity\Requisites;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LegalEntityControllerTest extends WebTestCase
{
    public function designersDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['phone' => UserFixtures::NEW_DESIGNER_PHONE])],
            [$userRepository->findOneBy(['phone' => UserFixtures::DESIGNER_PHONE])],
        ];
    }

    /**
     * @dataProvider designersDataProvider
     */
    public function testCanFillLegalEntity(User $designer): void
    {
        $client = static::createClient();
        $client->loginUser($designer);

        $crawler = $client->request('GET', '/designer/requisites/legal-entity');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="legal_entity_and_contract_number"]')->form([
            'legal_entity_and_contract_number[legalEntity]' => Requisites::LEGAL_ENTITY_SELF_EMPLOYED,
        ]);

        $client->followRedirects();
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertRouteSame('designer.requisites.personal_data');
    }

    /**
     * @dataProvider designersDataProvider
     */
    public function testCanFillContractNumber(User $designer): void
    {
        $client = static::createClient();
        $client->loginUser($designer);

        $crawler = $client->request('GET', '/designer/requisites/legal-entity');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="legal_entity_and_contract_number"]')->form([
            'hasContractNumber' => 'on',
            'legal_entity_and_contract_number[contractNumber]' => 'TEST-999',
        ]);

        $client->followRedirects();
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertRouteSame('designer.requisites.complete');
    }

    public function designersWithoutLegalEntityDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['email' => UserFixtures::DESIGNER_WITHOUT_REQUISITES_EMAIL])],
        ];
    }

    /**
     * @dataProvider designersWithoutLegalEntityDataProvider
     */
    public function testCanRedirectFromPersonalDataIfLegalEntityIsEmpty(User $designer): void
    {
        $client = static::createClient();
        $client->loginUser($designer);
        $client->followRedirects();
        $client->request('GET', '/designer/requisites/personal-data');
        $this->assertRouteSame('designer.requisites.legal_entity');
    }

    /**
     * @dataProvider designersWithoutLegalEntityDataProvider
     */
    public function testCanRedirectFromDocumentsUploadIfLegalEntityIsEmpty(User $designer): void
    {
        $client = static::createClient();
        $client->loginUser($designer);
        $client->followRedirects();
        $client->request('GET', '/designer/requisites/documents-upload');
        $this->assertRouteSame('designer.requisites.legal_entity');
    }
}
