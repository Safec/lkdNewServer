<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class ContactsController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route('/designer/contacts', name: 'designer.contacts')]
    public function __invoke(): Response
    {
        $managers = $this->userRepository->findActiveByRole(User::ROLE_MANAGER);

        return $this->render('designer/contacts.html.twig', [
            'managers' => $managers,
        ]);
    }

    #[Route('/designer/contacts-mobile', name: 'designer.contacts.mobile')]
    public function mobile(): Response
    {
        return $this->render('designer/contacts.mobile.html.twig');
    }
}
