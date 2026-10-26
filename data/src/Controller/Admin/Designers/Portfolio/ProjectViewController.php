<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Portfolio;

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
#[IsGranted(User::ROLE_MANAGER)]
class ProjectViewController extends AbstractController
{
    #[Route('/admin/designers/{designerId}/portfolio/{id}', name: 'admin.designer.portfolio.project.view')]
    public function __invoke(Request $request, Project $project): Response
    {
        return $this->render('admin/designer.project.view.html.twig', [
            'project' => $project,
        ]);
    }
}
