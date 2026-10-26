<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

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
#[IsGranted(User::ROLE_DESIGNER)]
class PortfolioController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {
    }

    #[Route('/designer/portfolio', name: 'designer.portfolio')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        //$user->isActive() || throw $this->createAccessDeniedException();

        $projects = $this->projectRepository->findByAuthor($user);

        return $this->render('designer/portfolio.html.twig', [
            'projects' => $projects,
        ]);
    }
}