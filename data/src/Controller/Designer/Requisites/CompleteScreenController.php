<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Requisites;

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
class CompleteScreenController extends AbstractController
{
    #[Route('/designer/requisites/complete', name: 'designer.requisites.complete')]
    public function __invoke(Request $request): Response
    {
        // Получаем сессию из запроса
        $session = $request->getSession();

        // Проверяем, установлена ли сессионная переменная "first_visit_requisites"
        if (!$session->has('first_visit_requisites')) {
            // Если переменная не установлена, значит это первый визит
            $session->set('first_visit_requisites', true);

            // Устанавливаем переменную только при первом посещении
            $openMenu = true;
        } else {
            $openMenu = false; // На последующих визитах меню будет скрыто
        }

        return $this->render('designer/requisites.complete.html.twig', [
            'open_menu' => $openMenu
        ]);
    }
}