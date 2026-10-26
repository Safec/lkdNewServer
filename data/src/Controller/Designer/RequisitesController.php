<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class RequisitesController extends AbstractController
{
    #[Route('/designer/requisites', name: 'designer.requisites')]
    public function __invoke(): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();

        if ($user->isFullyRegisteredDesigner() || $user->isReadyForModerate()) {
            return $this->redirectToRoute('designer.requisites.complete');
        } else {
            return $this->redirectToRoute('designer.requisites.legal_entity');
        }
    }
}
