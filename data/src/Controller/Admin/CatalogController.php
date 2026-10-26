<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Model;
use App\Entity\Document;
use App\Entity\Subcategory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ModelRepository;
use App\Repository\SubcategoryRepository;
use App\Form\Catalog\CatalogType;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ModelSaveService;
use App\Twig\Component\SearchModels;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CatalogController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, ModelSaveService $modelSaveService, private readonly SearchModels $searchModels)
    {
        $this->entityManager = $entityManager;
        $this->modelSaveService = $modelSaveService;
    }

   #[IsGranted(User::ROLE_MANAGER)]
   #[Route('/admin/catalog', name: 'admin.catalog')]
   public function __invoke(Request $request, ModelRepository $modelRepository): Response
   {
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

       return $this->render('admin/models.live.html.twig', [
           'models' => $models,
           'per_page' => $perPage,
           'page' => $page,
           'sort' => $sort,
           'sortDirection' => $sortDirection,
           'categories' => $categories
       ]);



       /*return $this->render('admin/catalog.html.twig', [
           'models' => $modelRepository->findAll(),
       ]);*/
   }

   #[IsGranted(User::ROLE_MANAGER)]
   #[Route('/admin/catalog/new', name: 'admin.catalog.model_new', methods: ['GET', 'POST'])]
   #[Route('/admin/catalog/{id}/edit', name: 'admin.catalog.model_edit', methods: ['GET', 'POST'])]
   public function redModel(Request $request, ?Model $model = null): Response
   {
      if (!$model) {
       $model = new Model();
      }

       $form = $this->createForm(CatalogType::class, $model);
       $form->handleRequest($request);

       if ($form->isSubmitted()) {

           $model = $form->getData();

           /** @var UploadedFile[]|null $uploadedFiles */
           $document = $form->get('previewImage')->getData();

          if ($document) {
                  if ($document instanceof Document) {
                      $this->modelSaveService->saveDocument($document);
                      $files = $document->getFiles();

                       if (!empty($files)) {
                           // Объединяем пути всех файлов в одну строку через запятую
                           //$allFiles = implode(',', $files);
                           //var_dump($files);

                           // Сохраняем эту строку в поле previewImage модели
                           $model->setPreviewImage($files);
                       }
                  } else {
                      //dump('Не документ:', get_class($document), $document);
                  }
          } else {
              $model->setPreviewImage(null);
          }



          if ($form->has('images'))
           $this->modelSaveService->saveImages($model)->flush();

           $this->modelSaveService->save($model)->flush();
           $this->addFlash('success', 'Модель успешно сохранена.');

           return $this->redirectToRoute('admin.catalog', [], Response::HTTP_SEE_OTHER);
       } elseif ($form->isSubmitted() && !$form->isValid()) {
           $postData = $request->request->all();
           // For debugging, you could dump POST data (this will show in the server log or response, not recommended in production)
           //var_dump($postData);
           // Если форма отправлена, но не прошла валидацию
           $errors = $form->getErrors(true, false); // Получение всех ошибок валидации
           // Вывод ошибок
           foreach ($errors as $error) {
               echo $error->getMessage() . '<br>';
           }
           die();
       }

       return $this->render('admin/catalog.model_new.html.twig', [
           'model' => $model,
           'form' => $form->createView(),
       ]);
   }

   #[IsGranted(User::ROLE_MANAGER)]
   #[Route('/admin/catalog/{id}', name: 'admin.catalog.model_delete', methods: ['POST'])]
    public function deleteModel(Request $request, Model $model, ModelRepository $modelRepository): Response
   {
       $this->modelSaveService->removeFiles($model);

       // Удаление модели через EntityManager
       $this->entityManager->remove($model);
       $this->entityManager->flush(); // Сохранение изменений в базе данных

       $this->addFlash('success', "Модель удалена.");

       return $this->redirectToRoute('admin.catalog');
   }

   #[Route('/admin/get-subcategories', name: 'admin.catalog.get_subcategories', methods: ['GET'])]
    public function getSubcategories(Request $request): JsonResponse
    {
        $categoryId = $request->query->get('category_id');
        $subCategories = $this->entityManager
            ->getRepository(Subcategory::class)
            ->findBy(['category' => $categoryId]);
        $subCategoriesData = array_map(function ($subCategory) {
            return [
                'id' => $subCategory->getId(),
                'name' => $subCategory->getName(),
            ];
        }, $subCategories);
        return new JsonResponse([
            'subcategories' => $subCategoriesData,
        ]);
    }


   #[Route('/catalog/{id}/subcategories', name: 'catalog.subcategories', methods: ['GET'])]
   public function getSubCat(int $id, SubcategoryRepository $subCategoryRepository): JsonResponse
   {
       $subcategories = $subCategoryRepository->findBy(['category' => $id]);

       // Формируем JSON для подкатегорий
       $data = [];
       foreach ($subcategories as $subcategory) {
           $data[] = [
               'id' => $subcategory->getId(),
               'name' => $subcategory->getName(),
           ];
       }

       return new JsonResponse($data);
   }

   #[Route('/catalog/models/update', name: 'catalog.models_update', methods: ['POST'])]
   public function updateModels(Request $request, ModelRepository $modelRepository): JsonResponse
   {
       $data = json_decode($request->getContent(), true);
       $subcategoryIds = $data['subcategoryIds'] ?? [];

       $page = 1;
       $sort = 'name';
       $sortDirection = 'asc';
       $perPage = 500;

       $models = $modelRepository->search(
           [
               'per_page' => $perPage,
               'page' => $page,
               'subcategories' => $subcategoryIds
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

       if ($this->isGranted(User::ROLE_MANAGER)) {
           $modelsHtml = $this->renderView('admin/models_list.html.twig', [
               'models' => $models
           ]);
       } elseif ($this->isGranted(User::ROLE_DESIGNER)) {
           $modelsHtml = $this->renderView('designer/models_list.html.twig', [
               'models' => $models
           ]);
       }

       return new JsonResponse(['modelsHtml' => $modelsHtml]);
   }

}