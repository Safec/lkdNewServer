<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Portfolio;

use App\Entity\Project;
use App\Entity\User;
use App\Form\Designer\ProjectType;
use App\Service\ProjectSaveService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class ProjectEditController extends AbstractController
{
    public function __construct(
        private readonly ProjectSaveService $projectSaveService,
    ) {
    }

    #[Route('/designer/portfolio/new-project', name: 'designer.portfolio.project.new')]
    #[Route('/designer/portfolio/{slug}/edit', name: 'designer.portfolio.project.edit')]
    public function __invoke(Request $request, ?Project $project = null): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        $user->isActive() || throw $this->createAccessDeniedException();

        if ($project) {
            $project->getAuthor()?->getId() == $user->getId() || throw $this->createAccessDeniedException();
        } else {
            $project = new Project();
            $project->setAuthor($user);
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Project $project */
            $project = $form->getData();
            $this->projectSaveService->save($project)->flush();
            $this->addFlash('success', "Проект «{$project->getTitle()}» сохранен.");

            return $this->redirectToRoute('designer.portfolio');
        }

        return $this->render('designer/project.edit.html.twig', [
            'form' => $form,
        ]);
    }
}
