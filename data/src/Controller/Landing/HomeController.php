<?php

/** @noinspection PhpUnused */

namespace App\Controller\Landing;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HomeController extends AbstractController
{
    #[Route('/landing/{name}', name: 'landing')]
    public function __invoke(string $name): Response
    {
        switch ($name) {
            case 'askona_design':
                return $this->render('landing/askona_design.html.twig');
            case 'konkurs':
                return $this->render('landing/konkurs.html.twig');
            default:
                throw $this->createNotFoundException();
                break;
        }
    }

    #[Route('/landing/konkurs/project', name: 'landing_konkurs', methods: ['POST'])]
    public function project(
        Request $request,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): JsonResponse {

        $name = trim($request->request->get('name'));
        $phone = trim($request->request->get('phone'));
        $userEmail = trim($request->request->get('email'));
        $nomination = trim($request->request->get('nomination'));
        $url = trim($request->request->get('url'));

        // Валидация
        if (!$name || !$phone || !$userEmail || !$nomination || !$url) {
            $logger->warning('Konkurs form validation failed', compact(
                'name', 'phone', 'userEmail', 'nomination', 'url'
            ));

            return new JsonResponse([
                'status' => 'error',
                'error' => 'Заполните все поля'
            ]);
        }

        // Формируем письмо
        $mail = (new Email())
            ->from('info.design.ask@mail.ru')
            ->to('askona_design@askona.ru')
            ->sender('info.design.ask@mail.ru')
            ->replyTo($userEmail)
            ->cc('ovmaria3@gmail.com', 'shoronovig@gruzstd.ru')
            ->subject('Проект со страницы конкурса Askona Design')
            ->html("
            <b>Имя:</b> {$name}<br>
            <b>Телефон:</b> {$phone}<br>
            <b>Email:</b> {$userEmail}<br>
            <b>Номинация:</b> {$nomination}<br>
            <b>Ссылка на проект:</b> {$url}<br>
        ");

        // Логируем попытку
        $logger->info('Konkurs mail send attempt', [
            'name' => $name,
            'phone' => $phone,
            'email' => $userEmail,
            'nomination' => $nomination,
            'url' => $url,
            'time' => date('Y-m-d H:i:s')
        ]);

        try {
            $mailer->send($mail);

            $messageId = null;
            if ($mail->getHeaders()->has('Message-ID')) {
                $messageId = $mail->getHeaders()->get('Message-ID')->getBody();
            }

            $logger->info('Konkurs mail sent SUCCESS', [
                'email' => $userEmail,
                'message_id' => $messageId
            ]);

            return new JsonResponse(['status' => 'success']);

        } catch (TransportExceptionInterface $e) {

            $logger->error('Konkurs mail FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $userEmail
            ]);

            return new JsonResponse([
                'status' => 'error',
                'error' => 'Ошибка отправки письма'
            ]);
        }
    }

    #[Route('/landing/askona_design/question', name: 'landing_question', methods: ['POST'])]
    public function question(
        Request $request,
        MailerInterface $mailer
    ): JsonResponse {

        $name = trim($request->request->get('name'));
        $phone = trim($request->request->get('phone'));
        $question = trim($request->request->get('question'));

        if (!$name || !$phone || !$question) {
            return new JsonResponse([
                'status' => 'error',
                'error' => 'Заполните все поля'
            ]);
        }

        $email = (new Email())
            ->from('info.design.ask@mail.ru')
            ->to('askona_design@askona.ru')
            ->subject('Вопрос с лендинга Askona Design')
            ->html("
            <b>Имя:</b> {$name}<br>
            <b>Телефон:</b> {$phone}<br><br>
            <b>Вопрос:</b><br>{$question}
        ");

        $mailer->send($email);

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
