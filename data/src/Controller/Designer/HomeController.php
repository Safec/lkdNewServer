<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HomeController extends AbstractController
{
    #[Route('/designer', name: 'designer.home')]
    public function __invoke(): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();

        if ($this->isGranted(User::ROLE_DESIGNER) && $user->isActive()) {
            return $this->redirectToRoute('designer.requisites');
        } elseif (!$user->getProfile()->getFullName()) {
            return $this->redirectToRoute('designer.profile');
        } elseif (!$user->getRequisites()->getLegalEntity()) {
            return $this->redirectToRoute('designer.requisites');
        } else {
            return $this->redirectToRoute('designer.requisites');
        }
    }
}