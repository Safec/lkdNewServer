<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers;

use App\Entity\User;
use App\Repository\DraftRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class DesignerDraftsController extends AbstractController
{
    public function __construct(
        private readonly DraftRepository $draftRepository,
    ) {
    }

    #[Route('/admin/designers/{id}/drafts', name: 'admin.designer.drafts')]
    public function __invoke(Request $request, User $designer): Response
    {
        $drafts = $this->draftRepository->findByAuthor($designer);

        return $this->render('admin/designer.drafts.html.twig', [
            'designer' => $designer,
            'drafts' => $drafts,
        ]);
    }
}