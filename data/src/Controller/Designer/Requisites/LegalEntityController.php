<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Requisites;

use App\Entity\Requisites;
use App\Entity\User;
use App\Form\Designer\LegalEntityAndContractNumberType;
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
class LegalEntityController extends AbstractController
{
    public function __construct(
        private readonly RequisitesRepository $requisitesRepository,
    ) {
    }

    #[Route('/designer/requisites/legal-entity', name: 'designer.requisites.legal_entity')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        !$user->isFullyRegisteredDesigner() || throw $this->createAccessDeniedException();
        !$user->isReadyForModerate() || throw $this->createAccessDeniedException();

        $requisites = $user->getRequisites();

        $form = $this->createForm(LegalEntityAndContractNumberType::class, $requisites);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Requisites $requisites */
            $requisites = $form->getData();

            $hasContractNumber = $request->request->get('hasContractNumber');
            if (!$hasContractNumber) {
                $requisites->setContractNumber(null);
            }

            $hasEdoNumber = $request->request->get('hasEdoNumber');
            if (!$hasEdoNumber) {
                $requisites->setEdoNumber(null);
            }

            $this->requisitesRepository->save($requisites)->flush();

            if (strlen((string)$requisites->getContractNumber()) > 0) {
                return $this->redirectToRoute('designer.requisites.complete');
            } else {
                return $this->redirectToRoute('designer.requisites.personal_data');
            }
        }
        #
        #print_r($form);

        return $this->render('designer/requisites.legal_entity.html.twig', [
            'form' => $form,
        ]);
    }
}