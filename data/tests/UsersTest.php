<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UsersTest extends KernelTestCase
{
    public function phoneDataProvider(): array
    {
        return [
            [UserFixtures::BOSS_PHONE],
            [UserFixtures::MANAGER_PHONE],
            [UserFixtures::DESIGNER_PHONE],
        ];
    }

    /**
     * @dataProvider phoneDataProvider
     */
    public function testCanGetUserByPhone($phone): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');

        /** @var UserRepository $userRepo */
        $userRepo = $doctrine->getRepository(User::class);

        $user = $userRepo->loadUserByIdentifier($phone);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($phone, $user->getPhone());
    }

    public function emailDataProvider(): array
    {
        return [
            [UserFixtures::BOSS_EMAIL],
            [UserFixtures::MANAGER_EMAIL],
            [UserFixtures::DESIGNER_EMAIL],
        ];
    }

    /**
     * @dataProvider emailDataProvider
     */
    public function testCanGetUserByEmail($email): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');

        /** @var UserRepository $userRepo */
        $userRepo = $doctrine->getManager()->getRepository(User::class);

        $user = $userRepo->loadUserByIdentifier($email);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
    }

    public function phoneNormalizationDataProvider(): array
    {
        return [
            ['+79201234567', '+79201234567'],
            ['79201234567', '+79201234567'],
            ['89201234567', '+79201234567'],
            ['9201234567', '+79201234567'],
            ['+7 920 123 45 67', '+79201234567'],
            ['7 920 123 45 67', '+79201234567'],
            ['8 920 123 45 67', '+79201234567'],
            ['920 123 45 67', '+79201234567'],
            ['+7(920)123-45-67', '+79201234567'],
            ['7(920)123-45-67', '+79201234567'],
            ['8(920)123-45-67', '+79201234567'],
            ['(920)123-45-67', '+79201234567'],
            ['+7 (920) 123-45-67', '+79201234567'],
            ['7 (920) 123-45-67', '+79201234567'],
            ['8 (920) 123-45-67', '+79201234567'],
            ['(920) 123-45-67', '+79201234567'],
            ['+7-920-123-45-67', '+79201234567'],
            ['7-920-123-45-67', '+79201234567'],
            ['8-920-123-45-67', '+79201234567'],
            ['920-123-45-67', '+79201234567'],
        ];
    }

    /**
     * @dataProvider phoneNormalizationDataProvider
     */
    public function testCanNormalizePhone($rawPhone, $normalizedPhone): void
    {
        $user = new User();
        $user->setPhone($rawPhone);
        $this->assertEquals(
            $normalizedPhone,
            $user->getPhone(),
            "Ошибка нормализации номера телефона $rawPhone. Ожидалось $normalizedPhone, получилось {$user->getPhone()}."
        );
    }

    public function emailNormalizationDataProvider(): array
    {
        return [
            ['user@example.com', 'user@example.com'],
            ['UsEr@ExAmPlE.CoM', 'user@example.com'],
            [' user@example.com  ', 'user@example.com'],
            ['', null],
            [' ', null],
        ];
    }

    /**
     * @dataProvider emailNormalizationDataProvider
     */
    public function testCanNormalizeEmail($rawEmail, $normalizedEmail): void
    {
        $user = new User();
        $user->setEmail($rawEmail);
        $this->assertEquals(
            $normalizedEmail,
            $user->getEmail(),
            "Ошибка нормализации Email $rawEmail. Ожидалось $normalizedEmail, получилось {$user->getEmail()}."
        );
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
    public function testExceptOnSaveInvalidPhone($invalidPhone): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');
        $objectManager = $doctrine->getManager();

        $user = new User();
        $user->setPassword('password');
        $phoneRef = new ReflectionProperty($user, 'phone');
        $phoneRef->setValue($user, $invalidPhone);

        $this->expectExceptionMessage('Invalid phone number');
        $objectManager->persist($user);
        $objectManager->flush();
    }

    public function invalidEmailDataProvider(): array
    {
        return [
            ['0'],
            ['NaN'],
            ['null'],
            ['undefined'],
            ['@'],
            ['@example.com'],
            ['user@example'],
            ['user@example.'],
        ];
    }

    /**
     * @dataProvider invalidEmailDataProvider
     */
    public function testExceptOnSaveInvalidEmail($invalidEmail): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');
        $objectManager = $doctrine->getManager();

        $user = new User();
        $user->setPassword('password');
        $emailRef = new ReflectionProperty($user, 'email');
        $emailRef->setValue($user, $invalidEmail);

        $this->expectExceptionMessage('Invalid email');
        $objectManager->persist($user);
        $objectManager->flush();
    }
}
