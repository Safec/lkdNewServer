<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Drafts;

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
#[IsGranted(User::ROLE_MANAGER)]
class DraftViewController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    #[Route('/admin/designers/{designerId}/drafts/{id}', name: 'admin.designer.draft.view')]
    public function __invoke(Request $request, Draft $draft): Response
    {
        return $this->render('admin/designer.draft.view.html.twig', [
            'designer' => $draft->getAuthor(),
            'draft' => $draft,
            'draft_products' => $this->productRepository->findParsedByDraft($draft),
        ]);
    }
}