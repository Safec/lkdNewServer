<?php

namespace App\Form\Designer;

use App\Validator;
use App\Entity\UserRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Service\Environment;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class ModelsRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('link', TextType::class, [
                'label' => 'Ссылка на нужный товар на сайте',
                'attr' => [
                    'placeholder' => 'Ссылка на товар'
                ],
            ])
            ->add('way', ChoiceType::class, [
                'label' => 'Куда отправить модель',
                'choices' => [
                    'WhatsApp' => 'WhatsApp',
                    'Telegram' => 'Telegram',
                    'Сообщение' => 'Сообщение',
                    'Звонок'=> 'Звонок'
                ],
            ])
            ->add('phone', options: [
                'label' => false,
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