<?php

/** @noinspection PhpUnused */

namespace App\Form\Designer;

use App\Entity\Project;
use App\Form\UploadFileType;
use App\Service\Environment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function Symfony\Component\Translation\t;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) {
            /** @var Project|mixed $data */
            $data = $event->getData();
            if ($data instanceof Project) {
                /** @noinspection DuplicatedCode */
                $coverIndex = (string)$data->getCover();
                $images = $data->getImages();
                if (array_key_exists($coverIndex, $images)) {
                    /** @var array $image */
                    $image = $images[$coverIndex];
                    $cover = isset($image['path']) && is_string($image['path']) ? $image['path'] : null;
                    $data->setCover($cover);
                }
            }
        });

        /** @noinspection DuplicatedCode */
        $builder
            ->add('images', CollectionType::class, [
                'entry_type' => UploadFileType::class,
                'required' => false,
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'mapped' => true,
                'entry_options' => [
                    'accept' => implode(', ', array_filter(Environment::getImageSupportedMimeTypes())),
                    'help' => sprintf(
                        "%s (до %s мб)",
                        implode(', ', array_filter(Environment::getImageSupportedExtensions())),
                        floor(Environment::getMaxFileSizeMb())
                    ),
                ],
                'constraints' => [
                    new Assert\Callback(function (array $images, ExecutionContextInterface $context) {
                        $images = array_filter($images, function (mixed $field) {
                            return is_array($field) && (!empty($field['file']) || !empty($field['path']));
                        });

                        $limit = 30;
                        if (count($images) > $limit) {
                            $context->buildViolation(
                                t('Должно быть не больше {{ limit }} изображений.'),
                                ['{{ limit }}' => $limit]
                            )->addViolation();
                        }
                    }),
                ],
            ])
            ->add('cover', HiddenType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('title', options: [
                'required' => true,
                'label' => t('form.project.title.label'),
                'attr' => [
                    'placeholder' => t('form.project.title.placeholder'),
                    'maxlength' => 50,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('subtitle', options: [
                'required' => true,
                'label' => t('form.project.subtitle.label'),
                'attr' => [
                    'placeholder' => t('form.project.subtitle.placeholder'),
                    'maxlength' => 50,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('description', options: [
                'required' => true,
                'label' => t('form.project.description.label'),
                'attr' => [
                    'placeholder' => t('form.project.description.placeholder'),
                    'maxlength' => 300,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => 300,
                    ]),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => t('form.save.label')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'attr' => [
                'data-maxsize' => Environment::getPostMaxSize(),
                'data-maxfiles' => min(30, Environment::getMaxFilesUploads()) + 1,
            ],
        ]);
    }
}
