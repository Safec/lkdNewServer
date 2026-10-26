<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

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
class ConditionsController extends AbstractController
{
    #[Route('/designer/conditions', name: 'designer.conditions')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        /*$user instanceof User || throw $this->createAccessDeniedException();
        $user->isActive() || throw $this->createAccessDeniedException();*/

        return $this->render('designer/conditions.html.twig');
    }
}