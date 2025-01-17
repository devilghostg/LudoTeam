<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Game;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Titre de l\'événement',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un titre']),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le titre doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Ex: Soirée jeux de société'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez votre événement...',
                    'rows' => 4
                ]
            ])
            ->add('date', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir une date']),
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'La date doit être dans le futur'
                    ])
                ]
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Nombre maximum de participants',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un nombre maximum de participants']),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Le nombre de participants doit être supérieur à 0'
                    ])
                ],
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Ex: 6'
                ]
            ])
            ->add('games', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'label' => 'Jeux proposés',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner au moins un jeu'])
                ],
                'attr' => [
                    'class' => 'select2'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
