<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Form\Designer\EventType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Message;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class EventsController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,
        private readonly EventRepository $eventRepository
    ) {
    }

    #[Route('/designer/events', name: 'designer.events')]
    public function __invoke(Request $request): Response
    {
        $events = $this->eventRepository->findBy([], ['date' => 'ASC']);

        return $this->render('designer/events.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/designer/events/{id}/view', name: 'designer.event.view')]
    public function viewEvent(Request $request, ?Event $event = null): Response
    {
        return $this->render('designer/events.view.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/designer/events/{id}/write', name: 'designer.event.write')]
    public function writeEvent(Request $request, int $id, EventRepository $eventRepository, UserInterface $user): Response
    {
        // Загрузка события из репозитория по ID
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

       $form = $this->createForm(EventType::class, null);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Event $event */
            $data = $form->getData();
            $data['title'] = $event->getTitle();
            $data['location'] = $event->getLocation();
            $data['date'] = $event->getDate()->format('d.m.Y');

            // Отправка email
            $email = (new Email())
                ->from('info.design.ask@mail.ru')
                ->to('askona_design@askona.ru')
                ->subject('Запись на мероприятие: '.$data['date'].', Локация: '.$data['location'])
                ->html($this->renderView('emails/events.html.twig', ['data' => $data]));

            if ($user instanceof User && $user->getRequisites() && $user->getRequisites()->getEmail())
                $email->addTo($user->getRequisites()->getEmail());

            $this->mailer->send($email);

            $this->addFlash('success', 'Вы записаны на мероприятие.');

            return $this->redirectToRoute('designer.event.view', ['id' => $id]);
        }

        return $this->render('designer/events.write.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }
}