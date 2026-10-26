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
class DesignerEditRequisitesController extends AbstractController
{
    #[Route('/admin/designers/{id}/edit/requisites', name: 'admin.designer.edit.requisites')]
    public function __invoke(Request $request, mixed $id): Response
    {
        return $this->redirectToRoute('admin.designer.requisites.legal_entity', [
            'id' => $id,
        ]);
    }
}