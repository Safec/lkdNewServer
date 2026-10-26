<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin;

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
class ManagersController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/admin/managers', name: 'admin.managers')]
    public function __invoke(Request $request): Response
    {
        $managers = $this->userRepository->findByRole(User::ROLE_MANAGER);

        return $this->render('admin/managers.html.twig', [
            'managers' => $managers,
        ]);
    }
}