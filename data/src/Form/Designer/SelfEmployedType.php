<?php

/** @noinspection PhpUnused */

namespace App\Form\Designer;

use App\Entity\Requisites;
use App\Validator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class SelfEmployedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', options: [
                'required' => true,
                'attr' => [
                    'placeholder' => t('form.full_name.placeholder'),
                ],
            ])
            ->add('phone', options: [
                'required' => true,
                'attr' => [
                    'placeholder' => t('form.phone.placeholder'),
                    'data-mask' => '+{7} (000) 000-00-00',
                    'maxlength' => '',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Validator\Phone(),
                ],
            ])
            ->add('email', options: [
                'required' => true,
                'attr' => [
                    'placeholder' => t('form.email.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('address', options: [
                'label' => t('form.registration_address.label'),
                'attr' => [
                    'placeholder' => t('form.address.placeholder'),
                ],
                'required' => true,
            ])
            ->add('INN', options: [
                'label' => t('form.INN.label'),
                'attr' => [
                    'placeholder' => t('form.INN.placeholder'),
                    'data-mask' => '0000000000[00]',
                ],
                'constraints' => [
                    new Assert\Length(min: 10, max: 12),
                    new Assert\Regex(pattern: '/^\d{10,12}$/'),
                ],
            ])
            /*->add('SNILS', options: [
                'required' => true,
                'label' => t('form.SNILS.label'),
                'attr' => [
                    'placeholder' => '123-456-789 01',
                    'data-mask' => '000-000-000 00',
                ],
                'constraints' => [
                    new Assert\Regex(pattern:
                        '/^\d{3}-\d{3}-\d{3} \d{2}$/',
                        message: t('form.SNILS.error', domain: 'validators')->getMessage()
                    ),
                ],
            ])*/
            ->add('passport', TextType::class, [
                'attr' => [
                    'placeholder' => t('form.passport.placeholder'),
                    'maxlength' => 1024,
                ],
            ])
            ->add('bankAccountNumber', options: [
                'attr' => [
                    'placeholder' => t('form.bank_account_number.placeholder'),
                    'data-mask' => '00000000000000000000',
                ],
                'constraints' => [
                    new Assert\Length(exactly: 20),
                    new Assert\Regex(pattern: '/^\d{20}$/'),
                ],
            ])
            ->add('bankCorrespondentAccount', options: [
                'attr' => [
                    'placeholder' => t('form.bank_correspondent_account.placeholder'),
                    'data-mask' => '00000000000000000000',
                ],
                'constraints' => [
                    new Assert\Length(exactly: 20),
                    new Assert\Regex(pattern: '/^\d{20}$/'),
                ],
            ])
            ->add('bank', options: [
                'label' => t('form.recipient_bank.label'),
                'attr' => [
                    'placeholder' => t('form.bank.placeholder'),
                ],
            ])
            ->add('BIK', options: [
                'label' => t('form.BIK.label'),
                'attr' => [
                    'placeholder' => t('form.BIK.placeholder'),
                    'data-mask' => '000000000',
                ],
                'constraints' => [
                    new Assert\Length(exactly: 9),
                    new Assert\Regex(pattern: '/^\d{9}$/'),
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