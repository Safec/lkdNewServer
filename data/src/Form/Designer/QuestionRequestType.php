<?php

namespace App\Form\Designer;

use App\Validator;
use App\Entity\UserRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Service\Environment;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class QuestionRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('question', TextareaType::class, [
                'label' => 'Ваш вопрос',
                'attr' => [
                    'placeholder' => 'Введите сюда вопрос'
                ],
            ])
            ->add('way', ChoiceType::class, [
                'label' => 'Куда отправить ответ',
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