<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Boss\ProfileType;
use App\Service\UserSaveService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly UserSaveService $userSaveService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/admin/profile', name: 'admin.profile')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();

        $form = $this->createForm(ProfileType::class, $user, [
            'password_required' => !$user->getPassword(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userSaveService->saveProfileForm($form)->flush();
                $this->addFlash('success', 'Профиль сохранён.');
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage() ?: get_class($e));
                $this->logger->error('Ошибка сохранения профиля пользователя #{id}.', ['id' => $user->getId()]);
                $this->addFlash('error', 'Не удалось сохранить профиль.');
            }

            return $this->redirect($request->getUri());
        }

        return $this->render('admin/profile.html.twig', [
            'form' => $form,
        ]);
    }
}