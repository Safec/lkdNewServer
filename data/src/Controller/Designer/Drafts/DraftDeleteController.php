<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Drafts;

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
#[IsGranted(User::ROLE_DESIGNER)]
class DraftDeleteController extends AbstractController
{
    public function __construct(
        private readonly DraftRepository $draftRepository,
    ) {
    }

    #[Route('/designer/drafts/{id}/delete', name: 'designer.draft.delete', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request, Draft $draft): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        $user->isActive() || throw $this->createAccessDeniedException();

        if ($draft->getAuthor()?->getId() == $user->getId()) {
            $this->draftRepository->remove($draft)->flush();
            $this->addFlash('success', "Черновик «{$draft->getTitle()}» удален.");
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->redirectToRoute('designer.drafts');
    }
}