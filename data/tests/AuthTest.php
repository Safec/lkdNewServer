<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\PasswordAuthenticator;
use App\Service\CaptchaService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\NotificationAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthTest extends WebTestCase
{
    use NotificationAssertionsTrait;

    public function phoneDataProvider(): array
    {
        return [
            [UserFixtures::BOSS_PHONE],
            [UserFixtures::MANAGER_PHONE],
            [UserFixtures::DESIGNER_PHONE],
            [UserFixtures::NEW_DESIGNER_PHONE],
        ];
    }

    /**
     * @dataProvider phoneDataProvider
     */
    public function testCanAuthWithSmsCode(string $phone): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => $phone]);
        $client->submit($form);

        $sms = $this->getNotifierMessage();

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $crawler->filter('.error')->each(function ($node) {
            throw new Exception($node->text());
        });

        $this->assertNotEmpty($sms);
        $this->assertInstanceOf(SmsMessage::class, $sms);
        $code = preg_replace('/\D/ui', '', $sms->getSubject());
        $this->assertNotEmpty($code);

        $form = $crawler->filter('form')->form(['_code' => $code]);
        $client->followRedirects();
        $client->submit($form);

        $this->assertResponseIsSuccessful();

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $client->getContainer()->get('security.token_storage');
        $user = $tokenStorage->getToken()?->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($phone, $user->getPhone());
    }

    public function testCanRegisterUserIfNotExistPhone(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => UserFixtures::UNREGISTERED_PHONE]);
        $client->submit($form);

        $sms = $this->getNotifierMessage();

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $crawler->filter('.error')->each(function ($node) {
            throw new Exception($node->text());
        });

        $this->assertNotEmpty($sms);
        $this->assertInstanceOf(SmsMessage::class, $sms);
        $code = preg_replace('/\D/ui', '', $sms->getSubject());
        $this->assertNotEmpty($code);

        $form = $crawler->filter('form')->form(['_code' => $code]);
        $client->followRedirects();
        $client->submit($form);

        $this->assertResponseIsSuccessful();

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $client->getContainer()->get('security.token_storage');
        $user = $tokenStorage->getToken()?->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(UserFixtures::UNREGISTERED_PHONE, $user->getPhone());
    }

    public function testShowErrorOnWrongSmsCode(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => UserFixtures::BOSS_PHONE]);
        $client->submit($form);

        $sms = $this->getNotifierMessage();

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $crawler->filter('.error')->each(function ($node) {
            throw new Exception($node->text());
        });

        $this->assertNotEmpty($sms);
        $this->assertInstanceOf(SmsMessage::class, $sms);
        $code = preg_replace('/\D/ui', '', $sms->getSubject());
        $this->assertNotEmpty($code);

        do {
            $wrongCode = random_int(1000, 9999);
        } while ($wrongCode === $code);

        $form = $crawler->filter('form')->form(['_code' => $wrongCode]);
        $client->followRedirects();
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Неверный код подтверждения');
    }

    public function testShowErrorOnExpiredSmsCode(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => UserFixtures::BOSS_PHONE]);
        $client->submit($form);

        $sms = $this->getNotifierMessage();

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $crawler->filter('.error')->each(function ($node) {
            throw new Exception($node->text());
        });

        $this->assertNotEmpty($sms);
        $this->assertInstanceOf(SmsMessage::class, $sms);
        $code = preg_replace('/\D/ui', '', $sms->getSubject());
        $this->assertNotEmpty($code);

        // В тестовом контейнере код действителен 3 секунды (см. config/services.yaml).
        sleep(4);

        $form = $crawler->filter('form')->form(['_code' => $code]);
        $client->followRedirects();
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Код подтверждения недействителен');
    }

    public function testShowErrorOnInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => UserFixtures::BOSS_PHONE]);
        $form->get('_csrf_token')->setValue('wrong_token');
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'CSRF');
    }

    public function testShowErrorOnInvalidCaptchaCsrfToken(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => UserFixtures::BOSS_PHONE]);
        $form->get('_captcha_csrf_token')->setValue('wrong_token');
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'CSRF');
    }

    public function invalidPhoneDataProvider(): array
    {
        return [
            ['0'],
            ['NaN'],
            ['null'],
            ['undefined'],
            ['+7920123456'],
            ['7920123456'],
            ['8920123456'],
            ['920123456'],
        ];
    }

    /**
     * @dataProvider invalidPhoneDataProvider
     */
    public function testShowErrorOnInvalidPhone(string $invalidPhone): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => $invalidPhone]);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Неверный номер телефона');
    }

    public function testCanSendSmsWithCaptchaVerification(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $captchaServiceMock = $this->createMock(CaptchaService::class);
        $captchaServiceMock->method('verify')->willReturn(true);

        $client->getContainer()->set(CaptchaService::class, $captchaServiceMock);
        $client->disableReboot();

        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => UserFixtures::DESIGNER_PHONE]);

        $crawler = $client->request(
            $form->getMethod(),
            $form->getUri(),
            array_merge(
                $form->getValues(),
                ['h-captcha-response' => 'FAKE_CAPTCHA']
            )
        );

        $this->assertResponseIsSuccessful();

        $crawler->filter('.error')->each(function ($node) {
            throw new Exception($node->text());
        });
    }

    public function testSendSmsShowErrorOnInvalidCaptcha(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $captchaServiceMock = $this->createMock(CaptchaService::class);
        $captchaServiceMock->method('verify')->willReturn(false);

        $client->getContainer()->set(CaptchaService::class, $captchaServiceMock);
        $client->disableReboot();

        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => UserFixtures::BOSS_PHONE]);

        $client->request(
            $form->getMethod(),
            $form->getUri(),
            array_merge(
                $form->getValues(),
                ['h-captcha-response' => 'WRONG_CAPTCHA']
            )
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Ошибка проверки капчи');
    }

    /**
     * @dataProvider usernamePasswordDataProvider
     */
    public function testPasswordAuthShowErrorOnInvalidCaptcha(string $username, string $password): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $captchaServiceMock = $this->createMock(CaptchaService::class);
        $captchaServiceMock->method('verify')->willReturn(false);

        $client->getContainer()->set(CaptchaService::class, $captchaServiceMock);
        $client->disableReboot();

        $crawler = $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form([
            '_username' => $username,
            '_password' => $password,
        ]);

        $client->request(
            $form->getMethod(),
            $form->getUri(),
            array_merge(
                $form->getValues(),
                ['h-captcha-response' => 'WRONG_CAPTCHA']
            )
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Ошибка проверки капчи');
    }

    public static function changedPhoneDataProvider(): array
    {
        return [
            [UserFixtures::BOSS_PHONE],
            [UserFixtures::MANAGER_PHONE],
            [''],
        ];
    }

    /**
     * @dataProvider changedPhoneDataProvider
     */
    public function testShowErrorOnChangePhoneAfterSendCode(string $changedPhone): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_phone' => UserFixtures::DESIGNER_PHONE]);
        $client->submit($form);

        $sms = $this->getNotifierMessage();

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $crawler->filter('.error')->each(function ($node) {
            throw new Exception($node->text());
        });

        $this->assertNotEmpty($sms);
        $this->assertInstanceOf(SmsMessage::class, $sms);
        $code = preg_replace('/\D/ui', '', $sms->getSubject());
        $this->assertNotEmpty($code);

        // Подменяем телефон на другой и пытаемся авторизоваться с кодом, отправленным на предыдущий телефон.
        $form = $crawler->filter('form')->form([
            '_phone' => $changedPhone,
            '_code' => $code,
        ]);

        $client->followRedirects();
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Неверный номер телефона');
    }

    public function usernamePasswordDataProvider(): array
    {
        return [
            [UserFixtures::BOSS_PHONE, UserFixtures::BOSS_PASSWORD],
            [UserFixtures::BOSS_EMAIL, UserFixtures::BOSS_PASSWORD],
            [UserFixtures::MANAGER_PHONE, UserFixtures::MANAGER_PASSWORD],
            [UserFixtures::MANAGER_EMAIL, UserFixtures::MANAGER_PASSWORD],
            [UserFixtures::DESIGNER_PHONE, UserFixtures::DESIGNER_PASSWORD],
            [UserFixtures::DESIGNER_EMAIL, UserFixtures::DESIGNER_PASSWORD],
        ];
    }

    /**
     * @dataProvider usernamePasswordDataProvider
     */
    public function testCanAuthWithPassword(string $username, string $password): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_username' => $username, '_password' => $password]);
        $client->submit($form);

        $this->assertResponseIsSuccessful();

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $client->getContainer()->get('security.token_storage');
        $user = $tokenStorage->getToken()?->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertContainsEquals($username, [$user->getPhone(), $user->getEmail()]);
    }

    /**
     * @dataProvider usernamePasswordDataProvider
     */
    public function testCanAuthWithPasswordAndCaptcha(string $username, string $password): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $captchaServiceMock = $this->createMock(CaptchaService::class);
        $captchaServiceMock->method('verify')->willReturn(true);

        $client->getContainer()->set(CaptchaService::class, $captchaServiceMock);
        $client->disableReboot();

        $crawler = $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_username' => $username, '_password' => $password]);

        $client->request(
            $form->getMethod(),
            $form->getUri(),
            array_merge(
                $form->getValues(),
                ['h-captcha-response' => 'FAKE_CAPTCHA']
            )
        );

        $this->assertResponseIsSuccessful();

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $client->getContainer()->get('security.token_storage');
        $user = $tokenStorage->getToken()?->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertContainsEquals($username, [$user->getPhone(), $user->getEmail()]);
    }

    /**
     * @dataProvider usernamePasswordDataProvider
     */
    public function testCanNotAuthWithPasswordAndInvalidCaptchaCsrfToken(string $username, string $password): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form([
            '_username' => $username,
            '_password' => $password,
            '_captcha_csrf_token' => 'invalid_token',
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'CSRF');
    }

    /**
     * @dataProvider usernamePasswordDataProvider
     */
    public function testShowErrorOnWrongPassword(string $username, string $password): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_username' => $username, '_password' => 'wrong_' . $password]);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Неверный логин или пароль');
    }

    public function testShowErrorOnWrongUsername(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_username' => 'wrong', '_password' => 'wrong']);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Неверный логин или пароль');
    }

    public function testCanNotAuthWithEmptyPassword(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_username' => UserFixtures::NEW_DESIGNER_PHONE, '_password' => '']);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Пароль не может быть пустым');
    }

    /**
     * @dataProvider usernamePasswordDataProvider
     */
    public function testCanNotAuthWithEmptyUsername(string $username, string $password): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $form = $crawler->filter('form')->form(['_username' => '', '_password' => $password]);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Неверный логин');
    }

    public function privateUrlsDataProvider(): array
    {
        return [
            ['/'],
            ['/auth'],
            ['/admin'],
        ];
    }

    /**
     * @dataProvider privateUrlsDataProvider
     */
    public function testCanRedirectToAuth(string $privateUrl): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $client->request('GET', $privateUrl);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('/auth/login', $client->getRequest()->getRequestUri());
        $this->assertSelectorTextContains('body', 'Войдите или зарегистрируйтесь');
    }

    public function userProvider(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);

        return [
            [$userRepository->findOneBy(['phone' => UserFixtures::BOSS_PHONE])],
            [$userRepository->findOneBy(['phone' => UserFixtures::MANAGER_PHONE])],
            [$userRepository->findOneBy(['phone' => UserFixtures::DESIGNER_PHONE])],
        ];
    }

    /**
     * @dataProvider userProvider
     */
    public function testCanRedirectAuthUserToHome(User $user): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $client->loginUser($user);

        $client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsNotWith('/auth', $client->getRequest()->getRequestUri());

        $client->request('GET', '/auth/manager');

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsNotWith('/auth', $client->getRequest()->getRequestUri());
    }

    /**
     * @dataProvider userProvider
     */
    public function testCanLogout(User $user): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $client->loginUser($user);

        $client->request('GET', '/auth/logout');

        $this->assertResponseIsSuccessful();
        $this->assertEquals('/auth/login', $client->getRequest()->getRequestUri());
        $this->assertSelectorTextContains('body', 'Войдите или зарегистрируйтесь');
    }

    public function testPasswordAuthenticatorCanGetLoginUrl(): void
    {
        /** @var PasswordAuthenticator $passwordAuthenticator */
        $passwordAuthenticator = static::getContainer()->get(PasswordAuthenticator::class);
        $ref = new \ReflectionMethod($passwordAuthenticator, 'getLoginUrl');
        /** @var string $url */
        $url = $ref->invoke($passwordAuthenticator, new Request());
        $this->assertEquals('/auth/manager', $url);
    }
}
