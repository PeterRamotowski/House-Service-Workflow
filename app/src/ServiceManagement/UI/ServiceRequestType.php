<?php

namespace App\ServiceManagement\UI;

use App\IdentityAccess\Domain\User;
use App\IdentityAccess\Infrastructure\Persistence\DoctrineUserRepository;
use App\Property\Domain\House;
use App\ServiceManagement\Domain\ServiceRequest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceRequestType extends AbstractType
{
    public function __construct(private DoctrineUserRepository $userRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('house', EntityType::class, [
                'class' => House::class,
                'choice_label' => 'name',
                'label' => 'service_request.field.house',
                'placeholder' => 'service_request.field.house_placeholder',
                'required' => true,
            ])
            ->add('serviceType', ChoiceType::class, [
                'label' => 'service_request.field.service_type',
                'choices' => [
                    'service.type.cleaning' => 'cleaning',
                    'service.type.deep_cleaning' => 'deep_cleaning',
                    'service.type.maintenance' => 'maintenance',
                    'service.type.inspection' => 'inspection',
                    'service.type.laundry' => 'laundry',
                    'service.type.garden_work' => 'garden_work',
                    'service.type.pool_maintenance' => 'pool_maintenance',
                ],
                'placeholder' => 'service_request.field.service_type_placeholder',
                'required' => true,
            ])
            ->add('scheduledDate', DateTimeType::class, [
                'label' => 'service_request.field.scheduled_date',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'service_request.field.description',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'service_request.field.notes',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('estimatedDuration', NumberType::class, [
                'required' => false,
                'label' => 'service_request.field.estimated_duration',
                'scale' => 2,
            ])
            ->add('assignedCleaner', EntityType::class, [
                'class' => User::class,
                'choices' => $this->userRepository->findCleaners(),
                'choice_label' => 'fullName',
                'label' => 'service_request.field.assigned_cleaner',
                'placeholder' => 'service_request.field.cleaner_placeholder',
                'required' => false,
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'service_request.field.priority',
                'choices' => [
                    'priority.low' => 'low',
                    'priority.normal' => 'normal',
                    'priority.high' => 'high',
                    'priority.urgent' => 'urgent',
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceRequest::class,
        ]);
    }
}
