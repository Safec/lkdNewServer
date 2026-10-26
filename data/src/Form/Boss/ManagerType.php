<?php

/** @noinspection PhpUnused */

namespace App\Form\Boss;

use App\Entity\User;
use App\Service\Environment;
use App\Validator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class ManagerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @noinspection DuplicatedCode */
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
                'attr' => [
                    'placeholder' => t('form.full_name.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Менеджер' => User::ROLE_MANAGER,
                    'Менеджер обращений' => User::ROLE_MANAGER_OBR,
                ],
                'multiple' => false, // Позволяет выбирать несколько ролей
                'expanded' => false, // Переключает на чекбоксы вместо выпадающего списка
                'label' => 'Роль менеджера',
                'mapped' => false,
                'data' => $options['data']->getRoles() ? $options['data']->getRoles()[0] : null,
            ])
            ->add('position', options: [
                'property_path' => 'profile.position',
                'required' => true,
                'attr' => [
                    'placeholder' => t('form.position.placeholder'),
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('phone', options: [
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
            ->add('login', options: [
                'required' => true,
                'attr' => [
                    'placeholder' => t('form.login.placeholder'),
                    'autocomplete' => 'new-login',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z]+$/',
                        'message' => t('Логин должен содержать только латинские буквы.'),
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'required' => $options['password_required'] ?? false,
                'label' => ($options['password_required'] ?? false) ? t('form.password.label') : t('form.new_password.label'),
                'attr' => [
                    'placeholder' => ($options['password_required'] ?? false) ? t('form.password.placeholder') : t('form.new_password.placeholder'),
                    'autocomplete' => 'new-password',
                ],
                'constraints' => array_filter([
                    ($options['password_required'] ?? false) ? new Assert\NotBlank() : null,
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => t('Пароль должен содержать не менее {{ limit }} символов.'),
                    ]),
                ]),
            ])
            ->add('active', options: [
                'required' => false,
                'label' => t('form.active.label'),
            ])
            ->add('save', SubmitType::class, [
                'label' => t('Save'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password_required' => false,
            'avatar_file_accept' => implode(', ', Environment::getImageSupportedMimeTypes()),
            'avatar_file_help' => sprintf(
                "%s\n(до %s мб)",
                implode(', ', Environment::getImageSupportedExtensions()),
                floor(Environment::getMaxFileSizeMb())
            ),
        ]);
    }
}
