<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Managers;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_BOSS)]
class ManagerDeleteController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/admin/managers/{id}/delete', name: 'admin.manager.delete', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request, User $manager): Response
    {
        $this->userRepository->remove($manager)->flush();
        $this->addFlash('success', "Менеджер «{$manager->getUserIdentifier()}» удален.");

        return $this->redirectToRoute('admin.managers');
    }
}