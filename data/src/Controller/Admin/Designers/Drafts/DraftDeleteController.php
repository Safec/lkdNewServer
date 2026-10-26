<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Drafts;

use App\Entity\Draft;
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
class DraftDeleteController extends AbstractController
{
    public function __construct(
        private readonly DraftRepository $draftRepository,
    ) {
    }

    #[Route('/admin/designers/drafts/{id}/delete', name: 'admin.designer.draft.delete', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request, Draft $draft): Response
    {
        $this->draftRepository->remove($draft)->flush();
        $this->addFlash('success', "Черновик «{$draft->getTitle()}» удален.");

        if ($draft->getAuthor()?->getId()) {
            return $this->redirectToRoute('admin.designer.drafts', ['id' => $draft->getAuthor()?->getId()]);
        } else {
            return $this->redirectToRoute('admin.designers');
        }
    }
}