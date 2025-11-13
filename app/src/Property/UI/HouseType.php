<?php

namespace App\Property\UI;

use App\IdentityAccess\Domain\User;
use App\IdentityAccess\Infrastructure\Persistence\DoctrineUserRepository;
use App\Property\Domain\House;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HouseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'house.field.name',
                'required' => true,
            ])
            ->add('address', TextType::class, [
                'label' => 'house.field.address',
                'required' => true,
            ])
            ->add('city', TextType::class, [
                'label' => 'house.field.city',
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'house.field.postal_code',
                'required' => false,
            ])
            ->add('country', TextType::class, [
                'label' => 'house.field.country',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'house.field.description',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('bedrooms', IntegerType::class, [
                'label' => 'house.field.bedrooms',
                'required' => true,
            ])
            ->add('bathrooms', IntegerType::class, [
                'label' => 'house.field.bathrooms',
                'required' => true,
            ])
            ->add('squareMeters', IntegerType::class, [
                'required' => true,
                'label' => 'house.field.square_meters',
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'house.field.owner',
                'placeholder' => 'house.field.owner_placeholder',
                'required' => true,
                'query_builder' => function (DoctrineUserRepository $repository) {
                    return $repository->createQueryBuilder('u')
                        ->andWhere('u.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('u.firstName', 'ASC');
                },
                'choice_filter' => function (?User $user) {
                    return $user && in_array('ROLE_OWNER', $user->getRoles(), true);
                },
            ])
            ->add('owners', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'house.field.co_owners',
                'help' => 'house.field.co_owners_help',
                'query_builder' => function (DoctrineUserRepository $repository) {
                    return $repository->createQueryBuilder('u')
                        ->andWhere('u.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('u.firstName', 'ASC');
                },
                'choice_filter' => function (?User $user) {
                    return $user && in_array('ROLE_OWNER', $user->getRoles(), true);
                },
                'attr' => [
                    'class' => 'select2',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => House::class,
        ]);
    }
}
