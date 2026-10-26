<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers;

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
#[IsGranted(User::ROLE_MANAGER)]
class DesignerDeleteController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/admin/designers/{id}/delete', name: 'admin.designer.delete')]
    public function __invoke(Request $request, User $designer): Response
    {
        $this->userRepository->remove($designer)->flush();
        $this->addFlash('success', "Дизайнер «{$designer->getUserIdentifier()}» удален.");

        return $this->redirectToRoute('admin.designers');
    }
}