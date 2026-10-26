<?php

/** @noinspection PhpUnused */

namespace App\Form\Designer;

use App\Entity\Profile;
use App\Entity\User;
use App\Service\Environment;
use App\Validator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class ProfileType extends AbstractType
{
    /** @noinspection SpellCheckingInspection */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array $disabledFields */
        $disabledFields = is_array($options['disabled_fields'] ?? null) ? $options['disabled_fields'] : [];

        $builder
            ->add('avatar_file', FileType::class, [
                'required' => false,
                'mapped' => false,
                'help' => $options['avatar_file_help'] ?? '',
                'attr' => [
                    'accept' => $options['avatar_file_accept'] ?? 'image/*',
                    'data-maxsize' => Environment::getMaxFileSize(),
                ],
            ])
            ->add('avatar', HiddenType::class, [
                'property_path' => 'profile.avatar',
                'required' => false,
            ])
            ->add('fullName', options: [
                'property_path' => 'profile.fullName',
                'required' => true,
                'disabled' => in_array('fullName', $disabledFields),
                'attr' => [
                    'placeholder' => t('form.full_name.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('phone', options: [
                'required' => true,
                'disabled' => in_array('phone', $disabledFields),
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
            ->add('email', options: [
                'required' => true,
                'disabled' => in_array('email', $disabledFields),
                'attr' => [
                    'placeholder' => t('form.email.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('city', options: [
                'property_path' => 'profile.city',
                'required' => true,
                'disabled' => in_array('city', $disabledFields),
                'attr' => [
                    'placeholder' => t('form.city.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('telegram', options: [
                'property_path' => 'profile.telegram',
                'attr' => [
                    'placeholder' => t('form.telegram.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('legalEntity', ChoiceType::class, [
                'property_path' => 'profile.legalEntity',
                'required' => true,
                'disabled' => in_array('legalEntity', $disabledFields),
                'choices' => [
                    (string) t('form.legal_entity.placeholder') => '',
                    (string) t('form.legal_entity.choices.ltd') => Profile::LEGAL_ENTITY_LTD,
                    (string) t('form.legal_entity.choices.individual') => Profile::LEGAL_ENTITY_INDIVIDUAL,
                    (string) t('form.legal_entity.choices.self_employed') => Profile::LEGAL_ENTITY_SELF_EMPLOYED,
                ],
                'choice_label' => function (mixed $choice, mixed $key) {
                    return t((string)$key);
                },
                'choice_attr' => function (mixed $choice, mixed $key, mixed $value) {
                    return empty($value) ? ['disabled' => 'disabled', 'hidden' => 'hidden'] : [];
                },
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            /*->add('profession', ChoiceType::class, [
                'property_path' => 'profile.profession',
                'required' => false,
                'disabled' => in_array('profession', $disabledFields),
                'choices' => [
                    (string) t('form.profession.placeholder') => '',
                    (string) t('form.profession.choices.designer') => Profile::PROFESSION_DESIGNER,
                    (string) t('form.profession.choices.architect') => Profile::PROFESSION_ARCHITECT,
                    (string) t('form.profession.choices.interior_stylist') => Profile::PROFESSION_INTERIOR_STYLIST,
                ],
                'choice_label' => function (mixed $choice, mixed $key) {
                    return t((string)$key);
                },
                'choice_attr' => function (mixed $choice, mixed $key, mixed $value) {
                    return empty($value) ? ['disabled' => 'disabled', 'hidden' => 'hidden'] : [];
                }
            ])
            ->add('styles', ChoiceType::class, [
                'property_path' => 'profile.styles',
                'required' => false,
                'disabled' => in_array('style', $disabledFields),
                'multiple' => true,
                'choices' => [
                    (string) t('form.styles.placeholder') => '',
                    (string) t('Авангард') => 'Авангард',
                    (string) t('Английский стиль') => 'Английский стиль',
                    (string) t('Американский стиль') => 'Американский стиль',
                    (string) t('Античный стиль') => 'Античный стиль',
                    (string) t('Ампир') => 'Ампир',
                    (string) t('Арабский стиль') => 'Арабский стиль',
                    (string) t('Ар-деко') => 'Ар-деко',
                    (string) t('Ар-нуво') => 'Ар-нуво',
                    (string) t('Африканский стиль') => 'Африканский стиль',
                    (string) t('Барокко') => 'Барокко',
                    (string) t('Баухауз') => 'Баухауз',
                    (string) t('Бельгийский стиль') => 'Бельгийский стиль',
                    (string) t('Бидермейер') => 'Бидермейер',
                    (string) t('Бионика ') => 'Бионика ',
                    (string) t('Бохо') => 'Бохо',
                    (string) t('Брутализм') => 'Брутализм',
                    (string) t('Бунгало') => 'Бунгало',
                    (string) t('Венецианский стиль') => 'Венецианский стиль',
                    (string) t('Версаль') => 'Версаль',
                    (string) t('Викторианский стиль') => 'Викторианский стиль',
                    (string) t('Винтаж') => 'Винтаж',
                    (string) t('Восточный стиль') => 'Восточный стиль',
                    (string) t('Гжель') => 'Гжель',
                    (string) t('Голландский стиль') => 'Голландский стиль',
                    (string) t('Готический стиль') => 'Готический стиль',
                    (string) t('Гранж') => 'Гранж',
                    (string) t('Греческий стиль') => 'Греческий стиль',
                    (string) t('Джаз') => 'Джаз',
                    (string) t('Джапанди') => 'Джапанди',
                    (string) t('Датский стиль') => 'Датский стиль',
                    (string) t('Диско') => 'Диско',
                    (string) t('Египетский стиль') => 'Египетский стиль',
                    (string) t('Жуи') => 'Жуи',
                    (string) t('Ирландский стиль') => 'Ирландский стиль',
                    (string) t('Индонезийский стиль') => 'Индонезийский стиль',
                    (string) t('Индийский стиль') => 'Индийский стиль',
                    (string) t('Индустриальный стиль') => 'Индустриальный стиль',
                    (string) t('Испанский стиль') => 'Испанский стиль',
                    (string) t('Итальянский стиль') => 'Итальянский стиль',
                    (string) t('Кантри') => 'Кантри',
                    (string) t('Кельтский стиль') => 'Кельтский стиль',
                    (string) t('Китайский стиль') => 'Китайский стиль',
                    (string) t('Китч') => 'Китч',
                    (string) t('Классический стиль') => 'Классический стиль',
                    (string) t('Классицизм') => 'Классицизм',
                    (string) t('Колониальный стиль') => 'Колониальный стиль',
                    (string) t('Консерватизм') => 'Консерватизм',
                    (string) t('Конструктивизм') => 'Конструктивизм',
                    (string) t('Контемпорари') => 'Контемпорари',
                    (string) t('Кэжуал') => 'Кэжуал',
                    (string) t('Лаунж') => 'Лаунж',
                    (string) t('Лофт') => 'Лофт',
                    (string) t('Мавританский стиль') => 'Мавританский стиль',
                    (string) t('Манга') => 'Манга',
                    (string) t('Маньеризм') => 'Маньеризм',
                    (string) t('Марокканский стиль') => 'Марокканский стиль',
                    (string) t('Мексиканский стиль') => 'Мексиканский стиль',
                    (string) t('Милитари') => 'Милитари',
                    (string) t('Минимализм') => 'Минимализм',
                    (string) t('Модерн') => 'Модерн',
                    (string) t('Морской стиль') => 'Морской стиль',
                    (string) t('Неоклассика') => 'Неоклассика',
                    (string) t('Немецкий стиль') => 'Немецкий стиль',
                    (string) t('Норвежский стиль') => 'Норвежский стиль',
                    (string) t('Османский стиль') => 'Османский стиль',
                    (string) t('Парижский стиль') => 'Парижский стиль',
                    (string) t('Пин-ап') => 'Пин-ап',
                    (string) t('Поп-арт') => 'Поп-арт',
                    (string) t('Постмодернизм') => 'Постмодернизм',
                    (string) t('Прованс') => 'Прованс',
                    (string) t('Ренессанс') => 'Ренессанс',
                    (string) t('Ретро') => 'Ретро',
                    (string) t('Римский стиль') => 'Римский стиль',
                    (string) t('Рококо') => 'Рококо',
                    (string) t('Романский стиль') => 'Романский стиль',
                    (string) t('Романтизм') => 'Романтизм',
                    (string) t('Рустик') => 'Рустик',
                    (string) t('Русский стиль') => 'Русский стиль',
                    (string) t('Скандинавский стиль') => 'Скандинавский стиль',
                    (string) t('Славянский стиль') => 'Славянский стиль',
                    (string) t('Современный') => 'Современный',
                    (string) t('Средиземноморский стиль') => 'Средиземноморский стиль',
                    (string) t('Стимпанк') => 'Стимпанк',
                    (string) t('Техно') => 'Техно',
                    (string) t('Тиффани') => 'Тиффани',
                    (string) t('Тропический стиль') => 'Тропический стиль',
                    (string) t('Уличный стиль') => 'Уличный стиль',
                    (string) t('Французский стиль') => 'Французский стиль',
                    (string) t('Функциональный стиль') => 'Функциональный стиль',
                    (string) t('Футуризм') => 'Футуризм',
                    (string) t('Фьюжн') => 'Фьюжн',
                    (string) t('Хай-тек') => 'Хай-тек',
                    (string) t('Хиппи') => 'Хиппи',
                    (string) t('Хюгге') => 'Хюгге',
                    (string) t('Шале') => 'Шале',
                    (string) t('Шведский стиль') => 'Шведский стиль',
                    (string) t('Шебби-шик') => 'Шебби-шик',
                    (string) t('Эклектика') => 'Эклектика',
                    (string) t('Экостиль') => 'Экостиль',
                    (string) t('Экспрессионизм') => 'Экспрессионизм',
                    (string) t('Этнический стиль') => 'Этнический стиль',
                    (string) t('Японский стиль') => 'Японский стиль',
                ],
                'choice_label' => function (mixed $choice, mixed $key) {
                    return t((string)$key);
                },
                'choice_attr' => function (mixed $choice, mixed $key, mixed $value) {
                    return empty($value) ? ['disabled' => 'disabled', 'hidden' => 'hidden'] : [];
                },
                'attr' => [
                    'placeholder' => t('form.styles.placeholder'),
                    'multiple' => '',
                    'autocomplete' => 'off',
                    'class' => 'bubble-select',
                ]
            ])
            ->add('info', TextType::class, [
                'property_path' => 'profile.info',
                'required' => false,
                'disabled' => in_array('info', $disabledFields),
                'attr' => [
                    'placeholder' => t('form.info.placeholder'),
                ]
            ]) */
            ->add('sites', CollectionType::class, [
                'property_path' => 'profile.sites',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => t('Site'),
                'required' => false,
                'disabled' => in_array('sites', $disabledFields),
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => t('form.site.placeholder'),
                    ],
                    'row_attr' => [
                        'class' => 'm-0',
                    ],
                ]
            ])
            ->add('socialMedias', CollectionType::class, [
                'property_path' => 'profile.socialMedias',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => t('Social media'),
                'required' => false,
                'disabled' => in_array('socialMedias', $disabledFields),
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => t('form.social_media.placeholder'),
                    ],
                    'row_attr' => [
                        'class' => 'm-0',
                    ],
                ]
            ])
            ->add('userAgreementAccepted', CheckboxType::class, [
                'property_path' => 'profile.userAgreementAccepted',
                'disabled' => in_array('userAgreementAccepted', $disabledFields),
                'required' => true,
                'data' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('userAgreementAcceptedReklama', CheckboxType::class, [
                'property_path' => 'profile.userAgreementAcceptedReklama',
                'disabled' => in_array('userAgreementAcceptedReklama', $disabledFields),
                'required' => false,
                'data' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => t('form.save.label'),
            ])
        ;

        /** @var array $withOptionalFields */
        $withOptionalFields = is_array($options['with_optional_fields'] ?? null) ? $options['with_optional_fields'] : [];

        if (in_array('active', $withOptionalFields)) {
            $builder->add('active', CheckboxType::class, [
                'required' => false,
                'disabled' => in_array('active', $disabledFields),
                'label' => t('form.active.label'),
            ]);
        }

        /** @var array $withoutFields */
        $withoutFields = is_array($options['without_fields'] ?? null) ? $options['without_fields'] : [];

        /** @var string|mixed $field */
        foreach ($withoutFields as $field) {
            if (is_string($field)) {
                $builder->remove($field);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'disabled_fields' => [],
            'without_fields' => [],
            'with_optional_fields' => [],
            'avatar_file_accept' => implode(', ', Environment::getImageSupportedMimeTypes()),
            'avatar_file_help' => sprintf(
                "%s\n(до %s мб)",
                implode(', ', Environment::getImageSupportedExtensions()),
                floor(Environment::getMaxFileSizeMb())
            ),
        ]);

        $resolver->setAllowedTypes('disabled_fields', 'array');
        $resolver->setAllowedTypes('without_fields', 'array');
        $resolver->setAllowedTypes('with_optional_fields', 'array');
    }
}
