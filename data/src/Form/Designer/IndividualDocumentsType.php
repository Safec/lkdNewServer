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

class IndividualDocumentsType extends AbstractType
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @noinspection DuplicatedCode */
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
            ->add('OGRNIP', UploadDocumentType::class, [
                'label' => t('documents.individual.OGRNIP'),
                'required' => true,
            ])
            /*->add('INN', UploadDocumentType::class, [
                'label' => t('documents.individual.INN'),
                'required' => true,
            ])*/
            ->add('bank_account', UploadDocumentType::class, [
                'label' => t('documents.individual.bank_account'),
                'required' => true,
            ])
            ->add('passport', UploadDocumentType::class, [
                'label' => t('documents.individual.passport'),
                'required' => false,
            ])
            ->add('diploma', UploadDocumentType::class, [
                'label' => t('documents.individual.diploma'),
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