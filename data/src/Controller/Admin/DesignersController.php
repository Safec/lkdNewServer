<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Twig\Component\SearchDesigners;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class DesignersController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SearchDesigners $searchDesigners,
    ) {
    }

    #[Route('/admin/designers', name: 'admin.designers')]
    public function __invoke(Request $request): Response
    {
        $page = 1;
        $sort = 'registeredAt';
        $sortDirection = 'asc';

        $view = (string)$request->getSession()->get('admin.designers.view');
        if ($view === 'table') {
            $perPage = $this->searchDesigners->tablePerPage;
        } else {
            $perPage = $this->searchDesigners->gridPerPage;
        }

        $designers = $this->userRepository->search(
            [
                'role' => User::ROLE_DESIGNER,
                'per_page' => $perPage,
                'page' => $page,
            ],
            $sort,
            $sortDirection
        );

        return $this->render('admin/designers.live.html.twig', [
            'designers' => $designers,
            'per_page' => $perPage,
            'page' => $page,
            'sort' => $sort,
            'sortDirection' => $sortDirection,
            'view' => $view,
        ]);
    }
}