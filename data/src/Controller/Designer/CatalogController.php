<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

use App\Entity\User;
use App\Entity\Model;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ModelRepository;
use App\Service\ModelSaveService;
use App\Twig\Component\SearchModelsDesigner;
use League\Flysystem\FilesystemOperator;
/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class CatalogController extends AbstractController
{
    public function __construct(EntityManagerInterface $entityManager, private readonly SearchModelsDesigner $searchModels, private readonly FilesystemOperator $publicStorage)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/designer/catalog', name: 'designer.catalog')]
    public function __invoke(Request $request, ModelRepository $modelRepository): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        //$user->isActive() || throw $this->createAccessDeniedException();

        $page = 1;
        $sort = 'name';
        $sortDirection = 'asc';

        $perPage = $this->searchModels->gridPerPage;

        $models = $modelRepository->search(
            [
                'per_page' => $perPage,
                'page' => $page,
            ],
            $sort,
            $sortDirection
        );

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('DISTINCT c')
            ->from('App\Entity\Category', 'c')
            ->join('c.subcategories', 'sc') // Здесь 'subcategories' - это связь в Category
            ->join('sc.models', 'm'); // Здесь 'models' - это связь в SubCategory

        $categories = $queryBuilder->getQuery()->getResult();

        return $this->render('designer/models.live.html.twig', [
            'models' => $models,
            'per_page' => $perPage,
            'page' => $page,
            'sort' => $sort,
            'sortDirection' => $sortDirection,
            'categories' => $categories
        ]);

    }
    
    #[Route('/designer/catalog/{id}', name: 'designer.catalog.model_view', methods: ['GET'])]
    public function viewModel(Model $model): Response
    {
        return $this->render('designer/catalog.model_view.html.twig', [
            'model' => $model,
        ]);
    }

    #[Route('/designer/model/{id}/download-documents', name: 'designer.catalog.model_download', methods: ['GET'])]
    public function downloadDocuments(Model $model): Response
    {
        $files = $model->getPreviewImage(); // Массив файлов

        foreach($files as $filePath) {
           // Чтение содержимого файла по пути
            $fileContent = $this->publicStorage->read($filePath);

            if ($fileContent === false) {
                throw new \Exception('Не удалось прочитать файл.');
            }

            // Возвращаем содержимое файла (например, для скачивания)
            return new Response($fileContent, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"'
            ]);
        }

        return new Response();
    }

    

}