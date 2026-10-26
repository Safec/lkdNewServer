<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Portfolio;

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
#[IsGranted(User::ROLE_MANAGER)]
class ProjectDeleteController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {
    }

    #[Route('/admin/designers/portfolio/{id}/delete', name: 'admin.designer.portfolio.project.delete', methods: [Request::METHOD_POST])]
    public function __invoke(Request $request, Project $project): Response
    {
        $this->projectRepository->remove($project)->flush();
        $this->addFlash('success', "Проект «{$project->getTitle()}» удален.");

        if ($project->getAuthor()?->getId()) {
            return $this->redirectToRoute('admin.designer.portfolio', ['id' => $project->getAuthor()?->getId()]);
        } else {
            return $this->redirectToRoute('admin.designers');
        }
    }
}