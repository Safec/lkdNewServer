<?php

namespace App\Tests\Controller\Designer;

use App\DataFixtures\UserFixtures;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AvatarService;
use Exception;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\NotificationAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;

class ProfileControllerTest extends WebTestCase
{
    use NotificationAssertionsTrait;

    public function designerProfileUserProvider(): Generator
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get('doctrine')->getRepository(User::class);

        $designers = [
            $repo->findOneBy(['phone' => UserFixtures::DESIGNER_PHONE]),
            $repo->findOneBy(['phone' => UserFixtures::NEW_DESIGNER_PHONE]),
        ];

        /** @var User $designer */
        foreach ($designers as $designer) {
            $avatarFilePath = tempnam(sys_get_temp_dir(), 'avatar_') . '.jpg';
            imagejpeg(imagecreatetruecolor(100, 100), $avatarFilePath);

            yield [
                $designer,
                [
                    'fullName' => $designer->getName(),
                    'phone' => $designer->getPhone(),
                    'email' => $designer->getEmail() ?: uniqid() . '@example.com',
                    'city' => 'Липецк',
                    'legalEntity' => Profile::LEGAL_ENTITY_SELF_EMPLOYED,
                    'profession' => Profile::PROFESSION_DESIGNER,
                    'avatar' => '',
                    'avatar_file' => new UploadedFile($avatarFilePath, 'avatar.jpg'),
                ]
            ];
        }
    }

    /**
     * @dataProvider designerProfileUserProvider
     */
    public function testErrorOnEmptyRequiredFields(User $designer, array $profile): void
    {
        $client = static::createClient();

        $client->loginUser($designer);
        $client->loginUser($designer);

        $crawler = $client->request('GET', '/designer/profile');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="profile"]')->form([
            'profile' => [
                'fullName' => '',
                'phone' => '',
                'email' => '',
                'city' => '',
                'legalEntity' => '',
                'profession' => '',
            ]
        ]);

        $values = $form->getPhpValues();
        $values['profile']['styles'] = [];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseIsUnprocessable();
        $this->assertStringContainsString('не должно быть пустым', $crawler->filter('.error')->text());
    }

    /**
     * @dataProvider designerProfileUserProvider
     */
    public function testCanFillProfile(User $designer, array $profile): void
    {
        $client = static::createClient();
        $client->loginUser($designer);

        $oldAvatar = $designer->getProfile()->getAvatar();

        $crawler = $client->request('GET', '/designer/profile');
        $form = $crawler->filter('form[name="profile"]')->form([
            'profile' => $profile
        ]);

        if ($form->has('profile[userAgreementAccepted]')) {
            $form['profile[userAgreementAccepted]']->tick();
        }

        $client->followRedirects();

        $values = $form->getPhpValues();
        $values['profile']['styles'] = ['Ретро', 'Футуризм'];

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseIsSuccessful('Не удалось сохранить профиль.');

        /** @var UserRepository $repository */
        $repository = static::getContainer()->get('doctrine')->getRepository(User::class);

        $saved = $repository->find($designer->getId());
        $this->assertInstanceOf(User::class, $saved);
        $this->assertInstanceOf(Profile::class, $saved->getProfile());

        $this->assertEquals($designer->getName(), $saved->getProfile()->getFullName());
        $this->assertEquals($designer->getPhone(), $saved->getProfile()->getPhone());
        $this->assertEquals('Липецк', $saved->getProfile()->getCity());
        $this->assertEquals(Profile::LEGAL_ENTITY_SELF_EMPLOYED, $saved->getProfile()->getLegalEntity());
        $this->assertEquals(Profile::PROFESSION_DESIGNER, $saved->getProfile()->getProfession());
        $this->assertEquals(['Ретро', 'Футуризм'], $saved->getProfile()->getStyles());
        $this->assertTrue($saved->getProfile()->isUserAgreementAccepted());
        $this->assertNotEquals($oldAvatar, $saved->getProfile()->getAvatar());
    }

    /**
     * @depends testErrorOnEmptyRequiredFields
     * @dataProvider designerProfileUserProvider
     */
    public function testShowErrorOnAvatarUploadFailure(User $designer, array $profile): void
    {
        $client = static::createClient();

        $avatarServiceMock = $this->createMock(AvatarService::class);
        $avatarServiceMock->method('save')->willThrowException(new Exception());

        $client->getContainer()->set(AvatarService::class, $avatarServiceMock);
        $client->disableReboot();

        $client->loginUser($designer);

        $crawler = $client->request('GET', '/designer/profile');
        $form = $crawler->filter('form[name="profile"]')->form([
            'profile' => $profile,
        ]);

        if ($form->has('profile[userAgreementAccepted]')) {
            $form['profile[userAgreementAccepted]']->tick();
        }

        $client->submit($form);

        /** @var Session $session */
        $session = $client->getRequest()->getSession();

        $this->assertStringContainsString('Ошибка загрузки аватара', $session->getFlashBag()->get('error')[0] ?? null);
    }
}
