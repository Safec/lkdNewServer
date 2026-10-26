<?php

/** @noinspection PhpUnused */

namespace App\Form;

use App\Service\Environment;
use App\Service\PrivateUploadService;
use App\Service\PublicUploadService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UploadFileType extends AbstractType
{
    public function __construct(
        private readonly PublicUploadService $publicUploadService,
        private readonly PrivateUploadService $privateUploadService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event) {
            /** @var string|mixed $data */
            $data = $event->getData();
            $event->setData([
                'file' => null,
                'path' => $data,
            ]);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            /** @var array|mixed $data */
            $data = $event->getData();

            if (is_array($data)) {
                /** @var UploadedFile|null $file */
                $file = $data['file'] ?? null;
                if ($file instanceof UploadedFile) {
                    if ($event->getForm()->getConfig()->getOption('is_private_file', false)) {
                        $data['path'] = $this->privateUploadService->uploadTemporary($file);
                    } else {
                        $data['path'] = $this->publicUploadService->uploadTemporary($file);
                    }
                }
            }

            $event->setData($data);
        });

        $builder
            ->add('file', FileType::class, [
                'required' => false,
                'label' => false,
                'help' => $options['help'] ?? '',
                'attr' => [
                    'accept' => $options['accept'] ?? '',
                    'data-maxsize' => Environment::getMaxFileSize(),
                ],
            ])
            ->add('path', HiddenType::class, [
                'required' => false,
                'label' => false,
                'constraints' => [
                    // new NotBlank(),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'accept' => '',
            'help' => '',
            'is_private_file' => false,
        ]);
    }
}