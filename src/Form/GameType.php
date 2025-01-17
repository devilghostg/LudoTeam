<?php

namespace App\Form;

use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\GreaterThan;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du jeu',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un nom']),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Les Aventuriers du Rail'
                ]
            ])
            ->add('maxPlayers', IntegerType::class, [
                'label' => 'Nombre maximum de joueurs',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un nombre maximum de joueurs']),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Le nombre de joueurs doit être supérieur à 0'
                    ])
                ],
                'attr' => [
                    'min' => 1,
                    'placeholder' => 'Ex: 4'
                ]
            ])
            ->add('gameType', ChoiceType::class, [
                'label' => 'Type de jeu',
                'choices' => [
                    'Jeu de plateau' => 'board',
                    'Jeu de cartes' => 'card',
                    'Jeu en duel' => 'duel'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir un type de jeu']),
                    new Choice([
                        'choices' => ['board', 'card', 'duel'],
                        'message' => 'Type de jeu invalide'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez le jeu...',
                    'rows' => 4
                ]
            ])
            ->add('isAvailable', CheckboxType::class, [
                'label' => 'Disponible pour les événements',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
