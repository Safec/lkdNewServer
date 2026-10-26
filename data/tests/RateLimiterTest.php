<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Notifier\Message\SmsMessage;

class RateLimiterTest extends WebTestCase
{
    public static function setUpBeforeClass(): void
    {
        $_ENV['RATE_LIMITER_POLICY'] = 'fixed_window';
        parent::setUpBeforeClass();
    }

    public function testOtpAuthShowErrorOnIpRateLimitExcepted(): void
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

        $client->followRedirects();

        $bannedRequests = 0;
        foreach (range(1, 10) as $i) {
            do {
                $wrongCode = rand(1000, 9999);
            } while ($wrongCode === $code);

            $form = $crawler->filter('form')->form(['_code' => $wrongCode]);
            $crawler = $client->submit($form);

            if (str_contains($crawler->filter('.error')->text(), 'превысили лимит попыток')) {
                $bannedRequests++;
            }
        }

        $this->assertNotEmpty($bannedRequests, 'Rate Limiter не заблокировал интенсивные запросы.');
    }

    public function testOtpAuthShowErrorOnPhoneRateLimitExcepted(): void
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

        $client->followRedirects();

        $bannedRequests = 0;
        foreach (range(1, 10) as $i) {
            do {
                $wrongCode = rand(1000, 9999);
            } while ($wrongCode === $code);

            $form = $crawler->filter('form')->form(['_code' => $wrongCode]);
            $crawler = $client->submit($form, serverParameters: ['REMOTE_ADDR' => '192.168.0.' . $i]);

            if (str_contains($crawler->filter('.error')->text(), 'превысили лимит попыток')) {
                $bannedRequests++;
            }
        }

        $this->assertNotEmpty($bannedRequests, 'Rate Limiter не заблокировал интенсивные запросы.');
    }

    public function testPasswordAuthShowErrorOnIpRateLimitExcepted(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/manager');

        $bannedRequests = 0;
        foreach (range(1, 10) as $i) {
            $form = $crawler->filter('form')->form([
                '_username' => 'wrong_username_' . $i,
                '_password' => 'wrong_password',
            ]);

            $crawler = $client->submit($form);

            $error = $crawler->filter('.error')->text();
            if (str_contains($error, 'превысили лимит попыток')) {
                $bannedRequests++;
            }
        }

        $this->assertNotEmpty($bannedRequests, 'Rate Limiter не заблокировал интенсивные запросы.');
    }

    public function testPasswordAuthShowErrorOnUsernameRateLimitExcepted(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/auth/manager');

        $bannedRequests = 0;
        foreach (range(1, 10) as $i) {
            $form = $crawler->filter('form')->form([
                '_username' => 'wrong_username',
                '_password' => 'wrong_password',
            ]);

            $crawler = $client->submit($form, serverParameters: ['REMOTE_ADDR' => '192.168.0.' . $i]);

            $error = $crawler->filter('.error')->text();
            if (str_contains($error, 'превысили лимит попыток')) {
                $bannedRequests++;
            }
        }

        $this->assertNotEmpty($bannedRequests, 'Rate Limiter не заблокировал интенсивные запросы.');
    }
}