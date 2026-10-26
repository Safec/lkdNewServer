<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Requisites;

use App\Entity\Requisites;
use App\Entity\User;
use App\Form\Designer\CompanyType;
use App\Form\Designer\IndividualType;
use App\Form\Designer\SelfEmployedType;
use App\Repository\RequisitesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class PersonalDataController extends AbstractController
{
    public function __construct(
        private readonly RequisitesRepository $requisitesRepository,
    ) {
    }

    #[Route('/designer/requisites/personal-data', name: 'designer.requisites.personal_data')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        !$user->isFullyRegisteredDesigner() || throw $this->createAccessDeniedException();
        !$user->isReadyForModerate() || throw $this->createAccessDeniedException();

        $requisites = $user->getRequisites();
        $user->setRequisites($requisites);

        if ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_LTD) {
            $formType = CompanyType::class;
            $template = 'designer/requisites.company.html.twig';
        } elseif ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_INDIVIDUAL) {
            $formType = IndividualType::class;
            $template = 'designer/requisites.individual.html.twig';
        } elseif ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_SELF_EMPLOYED) {
            $formType = SelfEmployedType::class;
            $template = 'designer/requisites.self_employed.html.twig';
        } else {
            $this->addFlash('warning', 'Сначала нужно выбрать форму собственности.');

            return $this->redirectToRoute('designer.requisites.legal_entity');
        }

        $form = $this->createForm($formType, $requisites);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Requisites $requisites */
            $requisites = $form->getData();
            $this->requisitesRepository->save($requisites)->flush();

            $this->addFlash('success', 'Персональные данные сохранены.');

            return $this->redirectToRoute('designer.requisites.documents_upload');
        }

        return $this->render($template, [
            'form' => $form,
        ]);
    }
}