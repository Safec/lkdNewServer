<?php

namespace App\Tests\Controller\Designer\Requisites;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PersonalDataControllerTest extends WebTestCase
{
    public function designersDataProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_SELF_EMPLOYED_EMAIL]),
                [
                    'self_employed' => [
                        'fullName' => 'Самозанятый Дизайнер',
                        'phone' => '+7 (999) 999-99-99',
                        'email' => 'mail@example.com',
                        'address' => 'г. Москва, ул. Земляничная, д. 1',
                        'INN' => '123456789012',
                        'SNILS' => '123-456-789 01',
                        'passport' => '№ 1234 567890, выдан 14.05.2008 отделением УФМС России по Липецкой области в Елецком районе, код подразделения 123-456',
                        'bankAccountNumber' => '12345678901234567890',
                        'bank' => 'Банк',
                        'bankCorrespondentAccount' => '12345678901234567890',
                        'BIK' => '123456789',
                    ],
                ],
            ],
            [
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_INDIVIDUAL_EMAIL]),
                [
                    'individual' => [
                        'fullName' => 'Индивидуальный Дизайнер',
                        'phone' => '+7 (999) 999-99-99',
                        'email' => 'mail@example.com',
                        'company' => 'ИП Индивидуальный Дизайнер',
                        'legalAddress' => 'г. Москва, ул. Фруктовая, д. 1',
                        'mailingAddress' => 'г. Москва, ул. Ягодная, д. 1',
                        'INN' => '123456789012',
                        'OGRNIP' => '123456789012345',
                        'passport' => '1234 567890',
                        'bankAccountNumber' => '12345678901234567890',
                        'bank' => 'Банк',
                        'bankCorrespondentAccount' => '12345678901234567890',
                        'BIK' => '123456789',
                    ],
                ]
            ],
            [
                $userRepository->findOneBy(['email' => UserFixtures::DESIGNER_LTD_EMAIL]),
                [
                    'company' => [
                        'fullName' => 'Дизайнер из компании',
                        'phone' => '+7 (999) 999-99-99',
                        'email' => 'mail@example.com',
                        'company' => 'ООО Дизайнеры',
                        'charter' => 'Устав компании №999 от 01.01.1990',
                        'legalAddress' => 'г. Москва, ул. Фруктовая, д. 1',
                        'mailingAddress' => 'г. Москва, ул. Ягодная, д. 1',
                        'INN' => '123456789012',
                        'KPP' => '123456789',
                        'OGRN' => '1234567890123',
                        'bankAccountNumber' => '12345678901234567890',
                        'bank' => 'Банк',
                        'bankCorrespondentAccount' => '12345678901234567890',
                        'BIK' => '123456789',
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider designersDataProvider
     */
    public function testCanFillPersonalData(User $designer, array $data): void
    {
        $client = static::createClient();
        $client->loginUser($designer);
        $client->followRedirects();

        $crawler = $client->request('GET', '/designer/requisites/personal-data');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form($data);
        $crawler = $client->submit($form);

        $crawler->filter('.error')->each(function ($node) {
            $this->fail($node->text());
        });

        $this->assertResponseIsSuccessful();
        $this->assertRouteSame('designer.requisites.documents_upload');
    }
}
