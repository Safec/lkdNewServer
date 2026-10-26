<?php

/** @noinspection PhpUnused */

namespace App\Form\Designer;

use Symfony\Component\Form\FormBuilderInterface;

use function Symfony\Component\Translation\t;

class LegalEntityAndContractNumberType extends LegalEntityType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('contractNumber', options: [
                'label' => t('form.contract_number.label'),
                'attr' => [
                    'placeholder' => t('form.contract_number.placeholder'),
                ],
            ])
            ->add('edoNumber', options: [
                'label' => t('form.edo_number.label'),
                'attr' => [
                    'placeholder' => t('form.edo_number.placeholder'),
                ],
            ])
        ;
    }
}
