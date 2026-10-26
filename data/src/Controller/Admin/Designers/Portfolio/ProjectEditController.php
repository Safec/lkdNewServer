<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Portfolio;

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
#[IsGranted(User::ROLE_MANAGER)]
class ProjectEditController extends AbstractController
{
    public function __construct(
        private readonly ProjectSaveService $projectSaveService,
    ) {
    }

    #[Route('/admin/designers/{designerId}/portfolio/{id}/edit', name: 'admin.designer.portfolio.project.edit')]
    public function __invoke(Request $request, Project $project): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Project $project */
            $project = $form->getData();
            $this->projectSaveService->save($project)->flush();
            $this->addFlash('success', "Проект «{$project->getTitle()}» сохранен.");

            return $this->redirect($request->getUri());
        }

        if ($project->getAuthor()?->getId()) {
            $returnPath = $this->generateUrl('admin.designer.portfolio', ['id' => $project->getAuthor()?->getId()]);
        } else {
            $returnPath = $this->generateUrl('admin.designers');
        }

        return $this->render('admin/designer.project.edit.html.twig', [
            'form' => $form,
            'return_path' => $returnPath,
        ]);
    }
}
