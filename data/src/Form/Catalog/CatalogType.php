<?php

namespace App\Form\Catalog;

use App\Entity\Model;
use App\Entity\Category;
use App\Entity\Subcategory;
use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use App\Form\UploadFileType;
use App\Form\UploadDocumentType;
use App\Service\Environment;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use function Symfony\Component\Translation\t;

class CatalogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Model $model */
        $model = $options['data'];

        /*$builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event) use ($options) {
                $model = $options['data'];

                $data = [];
                foreach ($event->getForm() as $field) {
                    if ($field->getConfig()->getOption('data_class') === Document::class) {
                        $document = new Document();
                        $document->setFiles($model->getPreviewImage());

                        $data[$field->getName()] = $document;
                        //dump($data);
                    }
                }
                $event->setData($data);

                dump($event->getData());
            });*/

       // dump($model);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) {
            /** @var Model|mixed $data */
            $data = $event->getData();
            if ($data instanceof Model) {
                /** @noinspection DuplicatedCode */
                $coverIndex = (string)$data->getCover();
                $images = $data->getImages();

                //dump($coverIndex, $images);
                if (array_key_exists($coverIndex, $images)) {
                    /** @var array $image */
                    $image = $images[$coverIndex];
                    $cover = isset($image['path']) && is_string($image['path']) ? $image['path'] : null;
                    $data->setCover($cover);
                }
            }
        });

      if (is_array($value = $model->getPreviewImage())) {
            $document = new Document();
            $document->setFiles($value);
        };

        $builder
            ->add('name', TextType::class, [
                'label' => 'Название (рекомендуем 3-6 слов)',
                'attr' => [
                    'placeholder' => 'Название модели'
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'Категория',
                'choice_label' => 'name',
                'placeholder' => 'Выберите категорию',
                'mapped' => false, // это поле не будет сохраняться напрямую в модель
                'required' => true,
                'data' => $model->getSubcategory() ? $model->getSubcategory()->getCategory() : null,
            ])
            ->add('subcategory', EntityType::class, [
                'class' => Subcategory::class,
                'label' => 'Подкатегория',
                'choice_label' => 'name',
                'placeholder' => 'Сначала выберите категорию',
                'required' => true,
            ])
            /*->add('subcategory', null, [
                'choice_label' => 'name',
                'label' => 'Подкатегория'
            ])*/
            ->add('previewImage', UploadDocumentType::class, [
                'required' => false,
                'label' => 'Файл модели',
                'accept' => 'application/zip,application/rar',
                'help' => 'zip, rar (до 200 Мб)',
                'mapped' => false,
                'data' => $document,
                'constraints' => [
                    new File([
                        'maxSize' => '200M',
                        'mimeTypes' => [
                            'application/zip',
                            'application/rar',
                            // другие допустимые типы MIME
                        ],
                        'mimeTypesMessage' => 'Пожалуйста, загрузите файл в формате ZIP или RAR.',
                    ])
                ]
            ])
            ->add('platform', ChoiceType::class, [
                'required' => false,
                'label' => 'Платформа',
                'attr' => [
                    'placeholder' => 'Платформа'
                ],
                'choices' => [
                    '3dsMax 2009 и ниже' => '3dsMax 2009 и ниже',
                    '3dsMax 2010 + fbx'  => '3dsMax 2010 + fbx',
                    '3dsMax 2010 + obj'  => '3dsMax 2010 + obj',
                    '3dsMax 2011 + fbx'  => '3dsMax 2011 + fbx',
                    '3dsMax 2011 + obj'  => '3dsMax 2011 + obj',
                    '3dsMax 2012 + fbx'  => '3dsMax 2012 + fbx',
                    '3dsMax 2012 + obj'  => '3dsMax 2012 + obj',
                    '3dsMax 2013 + fbx'  => '3dsMax 2013 + fbx',
                    '3dsMax 2013 + obj'  => '3dsMax 2013 + obj',
                    '3dsMax 2014 + fbx'  => '3dsMax 2014 + fbx',
                    '3dsMax 2014 + obj'  => '3dsMax 2014 + obj',
                    '3dsMax 2015 + fbx'  => '3dsMax 2015 + fbx',
                    '3dsMax 2015 + obj'  => '3dsMax 2015 + obj',
                    '3dsMax 2016 + fbx'  => '3dsMax 2016 + fbx',
                    '3dsMax 2016 + obj'  => '3dsMax 2016 + obj',
                    '3dsMax 2017 + fbx'  => '3dsMax 2017 + fbx',
                    '3dsMax 2017 + obj'  => '3dsMax 2017 + obj',
                    '3dsMax 2018 + fbx'  => '3dsMax 2018 + fbx',
                    '3dsMax 2018 + obj'  => '3dsMax 2018 + obj',
                    '3dsMax 2019 + fbx'  => '3dsMax 2019 + fbx',
                    '3dsMax 2019 + obj'  => '3dsMax 2019 + obj',
                    '3dsMax 2020 + fbx'  => '3dsMax 2020 + fbx',
                    '3dsMax 2020 + obj'  => '3dsMax 2020 + obj',
                    '3dsMax 2021 + fbx'  => '3dsMax 2021 + fbx',
                    '3dsMax 2021 + obj'  => '3dsMax 2021 + obj',
                    'fbx/obj'  => 'fbx/obj',
                    'Текстура'  => 'Текстура',
                    'Script'  => 'Script'
                ],
            ])
            ->add('render', ChoiceType::class, [
                'required' => false,
                'label' => 'Рендер',
                'choices' => [
                    'Vray'           => 'Vray',
                    'Standart'       => 'Standart',
                    'Corona'         => 'Corona',
                    'Vray + Corona'  => 'Vray + Corona'
                 ],
            ])
            ->add('images', CollectionType::class, [
                'entry_type' => UploadFileType::class,
                'required' => false,
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'mapped' => true,
                'entry_options' => [
                    'label' => 'Изображения для превью (не более 7)',
                    'accept' => implode(', ', array_filter(Environment::getImageSupportedMimeTypes())),
                    'help' => sprintf(
                        "%s (до 1 мб)",
                        implode(', ', array_filter(Environment::getImageSupportedExtensions()))
                    ),
                ],
                'constraints' => [
                    // Ограничение на количество файлов
                    new Assert\Callback(function (array $images, ExecutionContextInterface $context) {
                        $images = array_filter($images, function (mixed $field) {
                            return is_array($field) && (!empty($field['file']) || !empty($field['path']));
                        });

                        $limit = 7;
                        if (count($images) > $limit) {
                            $context->buildViolation(
                                t('Должно быть не больше {{ limit }} изображений.'),
                                ['{{ limit }}' => $limit]
                            )->addViolation();
                        }
                    }),
                    // Ограничение на размер каждого файла
                    new Assert\Callback(function (array $images, ExecutionContextInterface $context) {
                        foreach ($images as $image) {
                            if (isset($image['file']) && $image['file'] instanceof UploadedFile) {
                                if ($image['file']->getSize() > 1048576) { // 1 Мб = 1048576 байт
                                    $context->buildViolation(
                                        t('Размер файла не должен превышать 1 Мб.')
                                    )->addViolation();
                                }
                            }
                            // Ограничение на тип файла (только изображения)
                            $supportedMimeTypes = Environment::getImageSupportedMimeTypes();
                            if ($image['file'])
                                if (!in_array($image['file']->getMimeType(), $supportedMimeTypes)) {
                                    $context->buildViolation(
                                        t('Разрешены только изображения с типами: {{ types }}.'),
                                        ['{{ types }}' => implode(', ', $supportedMimeTypes)]
                                    )->addViolation();
                                }
                        }
                    }),
                ],
             ])
            ->add('cover', HiddenType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('url', TextType::class, [
                'label' => 'Товар на сайте',
                'attr' => [
                    'placeholder' => 'Ссылка на сайт'
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['save_label'] ?? t('documents.send'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Model::class,
            'attr' => [
                'data-maxsize' => Environment::getPostMaxSize(),
                'data-maxfiles' => min(30, Environment::getMaxFilesUploads()) + 1,
            ],
        ]);
    }
}
