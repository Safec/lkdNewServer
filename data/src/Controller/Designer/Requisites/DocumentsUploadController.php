<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Requisites;

use App\Entity\Document;
use App\Entity\Requisites;
use App\Entity\User;
use App\Form\Designer\CompanyDocumentsType;
use App\Form\Designer\IndividualDocumentsType;
use App\Form\Designer\SelfEmployedDocumentsType;
use App\Service\DocumentSaveService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class DocumentsUploadController extends AbstractController
{
    public function __construct(
        private readonly DocumentSaveService $documentSaveService,
        private MailerInterface $mailer
    ) {
    }

    #[Route('/designer/requisites/documents-upload', name: 'designer.requisites.documents_upload')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        //!$user->isFullyRegisteredDesigner() || throw $this->createAccessDeniedException();
        //!$user->isReadyForModerate() || throw $this->createAccessDeniedException();

        $requisites = $user->getRequisites();
        if ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_SELF_EMPLOYED) {
            $form = $this->createForm(SelfEmployedDocumentsType::class, $user);
        } elseif ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_INDIVIDUAL) {
            $form = $this->createForm(IndividualDocumentsType::class, $user);
        } elseif ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_LTD) {
            $form = $this->createForm(CompanyDocumentsType::class, $user);
        } else {
            $this->addFlash('warning', 'Сначала нужно выбрать форму собственности.');
            return $this->redirectToRoute('designer.requisites.legal_entity');
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array $data */
            $data = $form->getData();
            /** @var Document|mixed $document */
            foreach ($data as $document) {
                if ($document instanceof Document) {
                    $this->documentSaveService->save($document);
                }
            }

            $this->documentSaveService->flush();

            $this->addFlash('success', 'Документы загружены.');

            $email = (new Email())
                ->from('info.design.ask@mail.ru')
                ->to('askona_design@askona.ru')
                ->subject('Загружены документы дизайнером')
                ->text('Дизайнер '.$requisites->getFullName().', Email: '.$user->getEmail().' загрузил документы');

            $this->mailer->send($email);
            $this->addFlash('success', 'Оповещение отправлено на почту менеджеру');

            return $this->redirectToRoute('designer.requisites.complete');
        }

        return $this->render('designer/requisites.documents_upload.html.twig', [
            'form' => $form,
            'contract_url' => null,
            'back' => ($user->isFullyRegisteredDesigner() || $user->isReadyForModerate())?1:0
        ]);
    }
}