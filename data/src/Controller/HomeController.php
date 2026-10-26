<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HomeController extends AbstractController
{
    #[Route('/', name: 'app.home')]
    public function __invoke(Request $request): Response
    {

        if ($this->isGranted(User::ROLE_MANAGER)) {
            return $this->redirectToRoute('admin.home');
        }

        if ($this->isGranted(User::ROLE_MANAGER_OBR)) {
            return $this->redirectToRoute('admin.requests');
        }

        if ($this->isGranted(User::ROLE_DESIGNER)) {
            return $this->redirectToRoute('designer.home');
        }

        throw $this->createAccessDeniedException();
    }
}