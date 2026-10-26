<?php

namespace App\Form\Designer;

use App\Validator;
use App\Entity\UserRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Form\UploadFileType;
use App\Form\UploadDocumentType;
use App\Service\Environment;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class OrderNotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fullName', options: [
                'label' => 'Заказчик',
                'required' => true,
                'attr' => [
                    'placeholder' => t('form.full_name.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('phone', options: [
                'label' => 'Телефон заказчика',
                'required' => true,
                'attr' => [
                    'data-mask' => '+{7} (000) 000-00-00',
                    'placeholder' => t('form.phone.placeholder'),
                    'maxlength' => '',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Validator\Phone(),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Адрес доставки объекта',
                'attr' => [
                        'placeholder' => 'Адрес'
                ]
            ])
            ->add('productList',TextareaType::class, [
                'required' => false,
                'label' => 'Предварительный список товаров',
                'attr' => [
                    'placeholder' => 'Список товаров',
                    'maxlength' => 300,
                ]
            ])
            ->add('invoice', UploadDocumentType::class, [
                'required' => false,
                'label' => false,
                'accept' => 'application/pdf,image/jpeg,image/png',
                'help' => 'pdf, jpeg, png (до 2 Мб)'
            ])
            ->add('project_info', UploadDocumentType::class, [
                'required' => false,
                'label' => false,
                'accept' => 'application/pdf,image/jpeg,image/png',
                'help' => 'pdf, jpeg, png (до 2 Мб)'
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['save_label'] ?? t('documents.send'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null
        ]);
    }
}