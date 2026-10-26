<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers;

use App\Entity\User;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class DesignerPortfolioController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {
    }

    #[Route('/admin/designers/{id}/portfolio', name: 'admin.designer.portfolio')]
    public function __invoke(Request $request, User $designer): Response
    {
        $projects = $this->projectRepository->findByAuthor($designer);

        return $this->render('admin/designer.portfolio.html.twig', [
            'designer' => $designer,
            'projects' => $projects,
        ]);
    }
}
