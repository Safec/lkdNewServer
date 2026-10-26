<?php

/** @noinspection PhpUnused */

namespace App\Form\Boss;

use App\Entity\Event;
use App\Service\Environment;
use App\Validator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @noinspection DuplicatedCode */
        $builder
            ->add('title', TextType::class, [
                'label' => 'Заголовок (рекомендуем 3-6 слов)',
                'attr' => [
                    'placeholder' => 'Заголовок мероприятия'
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Локация',
                'attr' => [
                    'placeholder' => 'Введите локацию'
                ],
            ])
            ->add('date', DateTimeType::class, [
                'label' => 'Дата и время',
                'widget' => 'choice', // Указывает, что будут выпадающие списки
                'html5' => false,
                'format' => 'dd-MM-yyyy HH:mm', // Формат отображения даты и времени (день-месяц-год часы:минуты)
                'years' => range(date('Y'), date('Y') + 10), // Диапазон годов (текущий + 10 лет)
                'placeholder' => [
                    'year' => 'Год',
                    'month' => 'Месяц',
                    'day' => 'День',
                    'hour' => 'Час',
                    'minute' => 'Минута'
                ], // Placeholder для каждого выпадающего списка
                'required' => true
            ])
            ->add('cover_file', FileType::class, [
                'label' => 'Обложка',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => $options['avatar_file_accept'] ?? 'image/*',
                    'data-maxsize' => Environment::getMaxFileSize(),
                ],
            ])
            ->add('coverImage', HiddenType::class, [
                'required' => false,
            ])
            ->add('speaker_file', FileType::class, [
                'label' => 'Фото спикера',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => $options['avatar_file_accept'] ?? 'image/*',
                    'data-maxsize' => Environment::getMaxFileSize(),
                ],
            ])
            ->add('speakerImage', HiddenType::class, [
                'required' => false,
            ])
            ->add('mainText',TextareaType::class, [
                'required' => false,
                'label' => 'Основной текст',
                'attr' => [
                    'placeholder' => 'Введите текст',
                    'rows' => 10
                ]
            ])
            ->add('speakerName', TextType::class, [
                'required' => false,
                'label' => 'Имя спикера',
                'attr' => [
                    'placeholder' => 'Введите имя спикера'
                ],
            ])
            ->add('speakerText',TextareaType::class, [
                'required' => false,
                'label' => 'Текст спикера',
                'attr' => [
                    'placeholder' => 'Текст спикера',
                    'rows' => 10
                ]
            ])
            ->add('isRegistrationOpen', CheckboxType::class, [
                'label' => 'Открыта регистрация',
                'required' => false
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Статус',
                'choices' => [
                    'Черновик' => 'draft',
                    'Опубликовано' => 'published',
                ],
                'required' => true,
                'expanded' => true, // Это позволяет отобразить как радиокнопки
                'multiple' => false
            ])
            ->add('save', SubmitType::class, [
                'label' => t('Save'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class
        ]);
    }
}
