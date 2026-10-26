<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin;

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
class HomeController extends AbstractController
{
    #[Route('/admin', name: 'admin.home')]
    public function __invoke(Request $request): Response
    {
        return $this->redirectToRoute('admin.designers');
    }
}