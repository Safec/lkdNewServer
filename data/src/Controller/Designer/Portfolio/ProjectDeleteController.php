<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Portfolio;

use App\Entity\Project;
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
class ProjectDeleteController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {
    }

    #[Route('/designer/portfolio/{id}/delete', name: 'designer.portfolio.project.delete', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request, Project $project): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        $user->isActive() || throw $this->createAccessDeniedException();

        if ($project->getAuthor()?->getId() == $user->getId()) {
            $this->projectRepository->remove($project)->flush();
            $this->addFlash('success', "Проект «{$project->getTitle()}» удален.");
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->redirectToRoute('designer.portfolio');
    }
}