<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Events;

use App\Entity\Event;
use App\Entity\User;
use App\Form\Boss\EventType;
use App\Repository\EventRepository;
use Exception;
use App\Service\EventService;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class EventEditController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly EventService $eventService,
    ) {
    }

#[Route('/admin/events/new', name: 'admin.event.new')]
#[Route('/admin/events/{id}/edit', name: 'admin.event.edit')]
public function __invoke(Request $request, ?Event $event = null): Response
{
    $form = $this->createForm(EventType::class, $event);

    $form->handleRequest($request);

    if ($form->isSubmitted() && !$form->isValid()) {
        foreach ($form->getErrors(true) as $error) {
            dump($error->getMessage()); // Отображаем ошибки валидации
        }
        // Для более удобного просмотра можно использовать dump или просто echo
        dump($form->getErrors(true));
    }


    if ($form->isSubmitted() && $form->isValid()) {
        /** @var Event $event */
        $event = $form->getData();

        /** @var UploadedFile|mixed $coverFile */
        $coverFile = $form->get('cover_file')->getData();
        if ($coverFile instanceof UploadedFile) {
            try {
                $filepath = $this->eventService->saveCover($coverFile);
                $event->setCoverImage($filepath);
            } catch (FilesystemException|Exception $e) {
                $this->addFlash('error', 'sss');
            }
        }

        /** @var UploadedFile|mixed $speakerFile */
        $speakerFile = $form->get('speaker_file')->getData();
        if ($speakerFile instanceof UploadedFile) {
            try {
                $filepath = $this->eventService->saveSpeaker($speakerFile);
                $event->setSpeakerImage($filepath);
            } catch (FilesystemException|Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $this->eventRepository->save($event)->flush();
        $this->addFlash('success', 'Мероприятие успешно сохранено.');

        return $this->redirectToRoute('admin.event.edit', ['id' => $event->getId()]);
    }

    return $this->render('admin/event.edit.html.twig', [
        'form' => $form,
        'event' => $event,
    ]);
}
}