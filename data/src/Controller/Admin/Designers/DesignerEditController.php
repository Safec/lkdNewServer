<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class DesignerEditController extends AbstractController
{
    #[Route('/admin/designers/{id}/edit', name: 'admin.designer.edit')]
    public function __invoke(Request $request, User $designer): Response
    {
        $designer instanceof User || throw $this->createNotFoundException();
        $designer->hasRole(User::ROLE_DESIGNER) || throw $this->createNotFoundException();

        return $this->redirectToRoute('admin.designer.portfolio', [
            'id' => $designer->getId(),
        ]);
    }
}