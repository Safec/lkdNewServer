<?php

/** @noinspection PhpUnused */

namespace App\Controller\Public;

use App\Entity\Draft;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DraftController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    #[Route('/project/{slug}', name: 'public.draft')]
    public function __invoke(Request $request, Draft $draft): Response
    {
        $products = $this->productRepository->findParsedByDraft($draft);

        return $this->render('public/draft.html.twig', [
            'draft' => $draft,
            'draft_products' => $products,
        ]);
    }
}