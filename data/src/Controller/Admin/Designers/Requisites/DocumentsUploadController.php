<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Requisites;

use App\Entity\Document;
use App\Entity\Requisites;
use Psr\Log\LoggerInterface;
use App\Entity\User;
use App\Form\Designer\CompanyDocumentsType;
use App\Form\Designer\IndividualDocumentsType;
use App\Form\Designer\SelfEmployedDocumentsType;
use App\Service\DocumentSaveService;
use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use function Symfony\Component\Translation\t;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class DocumentsUploadController extends AbstractController
{
    public function __construct(
        private readonly DocumentSaveService $documentSaveService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/admin/designers/{id}/edit/requisites/documents-upload', name: 'admin.designer.requisites.documents_upload')]
    public function __invoke(Request $request, User $designer): Response
    {
        $requisites = $designer->getRequisites();

        if ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_SELF_EMPLOYED) {
            $form = $this->createForm(SelfEmployedDocumentsType::class, $designer, ['save_label' => t('Continue')]);
        } elseif ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_INDIVIDUAL) {
            $form = $this->createForm(IndividualDocumentsType::class, $designer, ['save_label' => t('Continue')]);
        } elseif ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_LTD) {
            $form = $this->createForm(CompanyDocumentsType::class, $designer, ['save_label' => t('Continue')]);
        } else {
            $this->addFlash('warning', 'Сначала нужно выбрать форму собственности.');

            return $this->redirectToRoute('admin.designer.requisites.legal_entity', ['id' => $designer->getId()]);
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

            return $this->redirectToRoute('admin.designer.requisites.contract_number', ['id' => $designer->getId()]);
        }

        return $this->render('admin/designer.requisites.documents_upload.html.twig', [
            'designer' => $designer,
            'form' => $form,
            'archive_url' => $this->generateUrl('admin.designer.requisites.archive_download', ['id' => $designer->getId()]),
            'contract_url' => $this->generateUrl('admin.designer.requisites.contract_download', ['id' => $designer->getId()]),
        ]);
    }

    #[Route('/admin/designers/{id}/edit/requisites/archive-download/', name: 'admin.designer.requisites.archive_download')]
    public function downloadArchive(int $id, DocumentRepository $documentRepository) : Response
    {
        $documents = $documentRepository->findBy(['relatedUser' => $id]);  // Предположим, что у вас есть связь между документами и пользователями

        // Создаем абсолютный путь для ZIP архива
        $zipFilePathEmpty = $this->getParameter('kernel.project_dir') . '/uploads/tmp/documents.zip';
        $zipFilePath = $this->getParameter('kernel.project_dir') . '/uploads/tmp/documents'.$id.'.zip';

        // Создаем ZIP архив
        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            $this->logger->error('Не удалось создать ZIP архив');
            return new Response('Не удалось создать ZIP архив', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        foreach ($documents as $document) {
            foreach($document->getFiles() as $filename) {
                $filePath = $this->getParameter('kernel.project_dir').'/uploads/'.$filename;
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, basename($filename));
                    } else {
                        $this->logger->error('Файл не найден: ' . $filePath);
                    }
            }
        }

        $zip->close();

        // Проверяем, существует ли архив и имеет ли он ненулевой размер
        if (!file_exists($zipFilePath)) {
            $this->logger->error('ZIP архив не найден после создания');
            $zipFilePath = $zipFilePathEmpty;
        }

        if (@filesize($zipFilePath) === 0) {
            $this->logger->error('ZIP архив пустой');
            $zipFilePath = $zipFilePathEmpty;
         }

        // Возвращаем файл на скачивание
        $response = new StreamedResponse(function() use ($zipFilePath) {
            readfile($zipFilePath);
        });

        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="documents.zip"');
        $response->headers->set('Content-Length', filesize($zipFilePath));

        return $response;
    }
}