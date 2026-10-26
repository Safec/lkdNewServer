<?php

/** @noinspection PhpUnused */

namespace App\Form\Designer;

use App\Entity\Document;
use App\Entity\User;
use App\Form\UploadDocumentType;
use App\Repository\DocumentRepository;
use App\Service\Environment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

class SelfEmployedDocumentsType extends AbstractType
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event) {
            /** @var User|mixed $user */
            $user = $event->getData();
            if ($user instanceof User) {
                /** @noinspection DuplicatedCode */
                $data = [];
                foreach ($event->getForm() as $field) {
                    if ($field->getConfig()->getOption('data_class') === Document::class) {
                        $document = $this->documentRepository->findOneByUserAndName($user, $field->getName());
                        if (!$document) {
                            $document = new Document();
                            $document->setRelatedUser($user);
                            $document->setName($field->getName());
                        }

                        $data[$field->getName()] = $document;
                    }
                }

                $event->setData($data);
            }
        });

        $builder
            ->add('passport', UploadDocumentType::class, [
                'label' => t('documents.self_employed.passport'),
                'required' => false,
            ])
            /*->add('INN', UploadDocumentType::class, [
                'label' => t('documents.self_employed.INN'),
                'required' => true,
            ])
            ->add('SNILS', UploadDocumentType::class, [
                'label' => t('documents.self_employed.SNILS'),
                'required' => true,
            ])*/
            ->add('certificate', UploadDocumentType::class, [
                'label' => t('documents.self_employed.certificate'),
                'required' => true,
            ])
            ->add('bank_account', UploadDocumentType::class, [
                'label' => t('documents.self_employed.bank_account'),
                'required' => true,
            ])
            ->add('diploma', UploadDocumentType::class, [
                'label' => t('documents.self_employed.diploma'),
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['save_label'] ?? t('documents.send'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'save_label' => t('documents.send'),
            'attr' => [
                'data-maxsize' => Environment::getPostMaxSize(),
                'data-maxfiles' => Environment::getMaxFilesUploads(),
            ]
        ]);
    }
}
