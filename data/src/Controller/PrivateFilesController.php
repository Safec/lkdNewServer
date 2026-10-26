<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Service\PrivateUploadService;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PrivateFilesController extends AbstractController
{
    public function __construct(
        private readonly PrivateUploadService $privateUploadService,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    #[Route('/private/{path}', name: 'private_file', requirements: ['path' => '.+'])]
    public function __invoke(Request $request, string $path): Response
    {
        try {
            $user = $this->getUser();
            $user instanceof User || throw $this->createAccessDeniedException();

            $this->privateUploadService->privateStorage->has($path) || throw new FileNotFoundException();

            $isManager = $this->authorizationChecker->isGranted('ROLE_MANAGER');
            if (!$isManager) {
                $documents = $this->documentRepository->findByPath($path);
                foreach ($documents as $document) {
                    $documentUserId = $document->getRelatedUser()?->getId();
                    $currentUserId = $user->getId();
                    if (!$documentUserId || !$currentUserId || $documentUserId !== $currentUserId) {
                        throw $this->createAccessDeniedException();
                    }
                }
            }

            $stream = $this->privateUploadService->privateStorage->readStream($path);
            $mime = $this->privateUploadService->privateStorage->mimeType($path) ?: 'application/octet-stream';

            return new StreamedResponse(
                function () use ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                },
                headers: [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'filename="' . basename($path) . '"',
                ]
            );
        } catch (FilesystemException|FileNotFoundException) {
            throw $this->createNotFoundException();
        }
    }
}