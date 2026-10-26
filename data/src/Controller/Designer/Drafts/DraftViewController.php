<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Drafts;

use App\Entity\Draft;
use App\Entity\User;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class DraftViewController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    #[Route('/designer/drafts/{slug}', name: 'designer.draft.view')]
    public function __invoke(Request $request, Draft $draft): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        $user->isActive() || throw $this->createAccessDeniedException();

        return $this->render('designer/draft.view.html.twig', [
            'draft' => $draft,
            'draft_products' => $this->productRepository->findParsedByDraft($draft),
        ]);
    }
}