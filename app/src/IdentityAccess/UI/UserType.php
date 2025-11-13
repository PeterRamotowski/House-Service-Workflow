<?php

namespace App\IdentityAccess\UI;

use App\IdentityAccess\Domain\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'user.field.email',
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'user.field.first_name',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 100]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'user.field.last_name',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 100]),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'user.field.phone',
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'user.field.password',
                'mapped' => false,
                'required' => $options['is_new_user'],
                'constraints' => $options['is_new_user'] ? [
                    new NotBlank(['message' => 'user.validation.password_required']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'user.validation.password_min_length',
                        'max' => 4096,
                    ]),
                ] : [],
                'attr' => [
                    'placeholder' => $options['is_new_user'] ? 'user.field.password_placeholder' : 'user.field.password_keep_current',
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'user.field.roles',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'role.user' => 'ROLE_USER',
                    'role.owner' => 'ROLE_OWNER',
                    'role.cleaner' => 'ROLE_CLEANER',
                    'role.manager' => 'ROLE_MANAGER',
                    'role.admin' => 'ROLE_ADMIN',
                ],
                'help' => 'user.field.roles_help',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'user.status.active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new_user' => false,
        ]);
    }
}
