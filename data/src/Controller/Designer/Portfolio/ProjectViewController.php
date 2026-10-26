<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Portfolio;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class ProjectViewController extends AbstractController
{
    #[Route('/designer/portfolio/{slug}', name: 'designer.portfolio.project.view')]
    public function __invoke(Request $request, Project $project): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        $user->isActive() || throw $this->createAccessDeniedException();

        return $this->render('designer/project.view.html.twig', [
            'project' => $project,
        ]);
    }
}