<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class MailTestController extends AbstractController
{
    #[Route('/test-mail', name: 'test_mail')]
    public function sendTestMail(): Response
    {
        // Указываем MAILER_DSN вручную
        $dsn = 'smtp://askona_design:Bh6g2XJAvfrW@mail.askona.ru:25?encryption=starttls&auth_mode=login';

        // Создаём транспорт
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from('askona_design@mail.askona.ru')
            ->to('shoronov.ivan@mail.ru')
            ->subject('Test Email')
            ->text('Это тестовое письмо из Symfony Mailer.');

        $mailer->send($email);

        return new Response('Тестовое письмо отправлено!');
    }
}