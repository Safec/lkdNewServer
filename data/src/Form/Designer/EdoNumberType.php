<?php

/** @noinspection PhpUnused */

namespace App\Form\Designer;

use App\Entity\Requisites;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class EdoNumberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('edoNumber', options: [
                'required' => true,
                'label' => t('form.edo_number.label'),
                'attr' => [
                    'placeholder' => t('form.edo_number.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['save_label'] ?? t('Save'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Requisites::class,
            'save_label' => t('Save'),
        ]);
    }
}
