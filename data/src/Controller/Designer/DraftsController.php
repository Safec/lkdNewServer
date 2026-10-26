<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

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
class DraftsController extends AbstractController
{
    public function __construct(
        private readonly DraftRepository $draftRepository,
    ) {
    }

    #[Route('/designer/drafts', name: 'designer.drafts')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        //$user->isActive() || throw $this->createAccessDeniedException();

        $drafts = $this->draftRepository->findByAuthor($user);

        return $this->render('designer/drafts.html.twig', [
            'drafts' => $drafts,
        ]);
    }
}