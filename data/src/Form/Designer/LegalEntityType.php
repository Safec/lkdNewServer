<?php

/** @noinspection PhpUnused */

namespace App\Form\Designer;

use App\Entity\Requisites;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class LegalEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('legalEntity', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    (string) t('form.legal_entity.placeholder') => '',
                    (string) t('form.legal_entity.choices.ltd') => Requisites::LEGAL_ENTITY_LTD,
                    (string) t('form.legal_entity.choices.individual') => Requisites::LEGAL_ENTITY_INDIVIDUAL,
                    (string) t('form.legal_entity.choices.self_employed') => Requisites::LEGAL_ENTITY_SELF_EMPLOYED,
                ],
                'choice_label' => function (mixed $choice, mixed $key) {
                    return t((string)$key);
                },
                'choice_attr' => function (mixed $choice, mixed $key, mixed $value) {
                    return empty($value) ? ['disabled' => 'disabled', 'hidden' => 'hidden'] : [];
                },
                'attr' => ['class' => 'js-legal-entity-selector mt-1 w-full'],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('taxSystem', ChoiceType::class, [
                'label' => t('form.taxSystem.label'),
                'required' => true,
                'choices' => [
                    '' => '',
                    'УСН' => 'usn',
                    'ОСНО' => 'osno',
                ],
                'attr' => ['class' => 'js-tax-system-selector mt-1 w-full', 'required'=>'required'],
                'empty_data' => null,
            ])
            ->add('vatRate', ChoiceType::class, [
                'label' => t('form.vatRate.label'),
                'required' => true,
                'choices' => [
                    '' => '',
                    'НДС 5%' => 'vat_5',
                    'НДС 7%' => 'vat_7',
                    'НДС 20%' => 'vat_20',
                    'БЕЗ НДС' => 'no_vat',
                    'С НДС' => 'with_vat',
                ],
                'attr' => ['class' => 'js-vat-rate-selector mt-1 w-full', 'required'=>'required'],
                'empty_data' => null,
            ])
            ->add('edoNumber', options: [
                'label' => t('form.edo_number.label'),
                'attr' => [
                    'placeholder' => t('form.edo_number.placeholder'),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['save_label'] ?? t('Continue'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Requisites::class,
            'save_label' => t('Continue'),
        ]);
    }
}
