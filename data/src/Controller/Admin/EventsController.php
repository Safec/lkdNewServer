<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\User;
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
class EventsController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository
    ) {
    }

#[Route('/admin/events', name: 'admin.events')]
public function __invoke(Request $request): Response
{
    $events = $this->eventRepository->findBy([], ['date' => 'ASC']);

    return $this->render('admin/events.html.twig', [
        'events' => $events,
    ]);
}
}