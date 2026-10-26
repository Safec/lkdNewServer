<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Events;

use App\Entity\User;
use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class EventDeleteController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    ) {
    }

    #[Route('/admin/events/{id}/delete', name: 'admin.event.delete', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request, Event $event): Response
    {
        $this->eventRepository->remove($event)->flush();
        $this->addFlash('success', "Мероприятие удалено.");

        return $this->redirectToRoute('admin.events');
    }
}