<?php

/** @noinspection PhpUnused */

namespace App\Form;

use App\Entity\Document;
use App\Service\Environment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\Translation\t;

class UploadDocumentType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('files', CollectionType::class, [
                'entry_type' => UploadFileType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'entry_options' => [
                    'is_private_file' => true,
                    'accept' => $options['accept'] ?? '',
                    'help' => $options['help'] ?? '',
                ],
                'constraints' => [
                    new Assert\Callback(function (mixed $value, ExecutionContextInterface $context) use ($options) {
                        if (empty($options['required'])) {
                            return;
                        }

                        $files = array_filter(is_array($value) ? $value : [], function ($file) {
                            return !empty($file['file']) || !empty($file['path']);
                        });

                        if (!count($files)) {
                            $context
                                ->buildViolation(
                                    t('form.document.required', [
                                        'label' => $options['label'] ?? '',
                                    ])->trans($this->translator)
                                )
                                ->addViolation();
                        }
                    }),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        /** @noinspection SpellCheckingInspection */
        $resolver->setDefaults([
            'data_class' => Document::class,
            'accept' => implode(', ', [
                // Images
                'image/*',
                // PDF
                'application/pdf',
                // MS Office
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                // OpenOffice
                'application/vnd.oasis.opendocument.text',
                'application/vnd.oasis.opendocument.spreadsheet',
                // LibreOffice
                'application/vnd.libreoffice.text',
                'application/vnd.libreoffice.spreadsheet',
                // Archives
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
            ]),
            'help' => t(
                'jpg, png, pdf, doc, docx, xls, xlsx, odt, ods, zip, rar, 7z (max {size})',
                ['size' => floor(Environment::getMaxFileSizeMb()) . ' мб']
            ),
        ]);
    }
}
