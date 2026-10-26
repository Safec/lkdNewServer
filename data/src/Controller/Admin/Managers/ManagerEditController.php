<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Managers;

use App\Entity\User;
use App\Form\Boss\ManagerType;
use App\Repository\UserRepository;
use App\Service\AvatarService;
use Exception;
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
#[IsGranted(User::ROLE_BOSS)]
class ManagerEditController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AvatarService $avatarService,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/admin/managers/new', name: 'admin.manager.new')]
    #[Route('/admin/managers/{id}/edit', name: 'admin.manager.edit')]
    public function __invoke(Request $request, ?User $manager = null): Response
    {
        if (!$manager) {
            $manager = new User();
            //$manager->setRoles([User::ROLE_MANAGER]);
            $manager->setActive(true);
        }

        $form = $this->createForm(ManagerType::class, $manager, [
            'password_required' => !$manager->getId(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $manager */
            $manager = $form->getData();

            // Получаем выбранную роль
            $selectedRole = $form->get('roles')->getData();
            $manager->setRoles([$selectedRole]); // Устанавливаем как массив

            /** @var UploadedFile|mixed $avatarFile */
            $avatarFile = $form->get('avatar_file')->getData();
            if ($avatarFile instanceof UploadedFile) {
                try {
                    $filepath = $this->avatarService->save($avatarFile);
                    $manager->getProfile()->setAvatar($filepath);
                } catch (FilesystemException|Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            $newPassword = (string)$form->get('password')->getData();
            if (strlen($newPassword)) {
                $manager->setPassword($this->passwordHasher->hashPassword($manager, $newPassword));
            }

            $this->userRepository->save($manager)->flush();
            $this->addFlash('success', 'Аккаунт менеджера успешно сохранен.');

            return $this->redirectToRoute('admin.manager.edit', ['id' => $manager->getId()]);
        }

        return $this->render('admin/manager.edit.html.twig', [
            'form' => $form,
            'manager' => $manager,
        ]);
    }
}