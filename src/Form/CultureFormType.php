<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Culture;
use App\Entity\Parcelle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

class CultureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomCulture', TextType::class, [
                'label' => 'Nom de la Culture',
                'trim' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de la culture est obligatoire.']),
                    new Length(min: 2, max: 80, minMessage: 'Le nom doit contenir au moins {{ limit }} caracteres.', maxMessage: 'Le nom ne doit pas depasser {{ limit }} caracteres.'),
                    new Regex(pattern: '/^[\p{L}][\p{L}\s\-\']*$/u', message: 'Le nom contient des caracteres invalides.'),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Ble, Tomates',
                    'maxlength' => 80,
                ],
            ])
            ->add('dateSemis', DateType::class, [
                'label' => 'Date de Semis',
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La date de semis est obligatoire.']),
                    new LessThanOrEqual('today', message: 'La date de semis ne peut pas etre dans le futur.'),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('etatCroissance', ChoiceType::class, [
                'label' => 'Etat de Croissance',
                'required' => true,
                'placeholder' => 'Selectionner un etat',
                'choices' => [
                    'Semis' => 'Semis',
                    'Croissance' => 'Croissance',
                    'Floraison' => 'Floraison',
                    'Recolte' => 'Recolte',
                    'Recolte termine' => 'Recolte termine',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L etat de croissance est obligatoire.']),
                    new Choice([
                        'choices' => ['Semis', 'Croissance', 'Floraison', 'Recolte', 'Recolte termine'],
                        'message' => 'Selection invalide pour l etat de croissance.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('rendementPrevu', NumberType::class, [
                'label' => 'Rendement Prevu',
                'required' => true,
                'scale' => 2,
                'html5' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le rendement prevu est obligatoire.']),
                    new Range(min: 0, max: 1000000, notInRangeMessage: 'Le rendement prevu doit etre entre {{ min }} et {{ max }}.'),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'min' => '0',
                    'max' => '1000000',
                    'step' => '0.01',
                ],
            ])
            ->add('parcelle', EntityType::class, [
                'label' => 'Parcelle',
                'class' => Parcelle::class,
                'choices' => $options['parcelle_choices'],
                'choice_label' => 'nomParcelle',
                'required' => true,
                'placeholder' => 'Selectionner une parcelle',
                'constraints' => [
                    new NotNull(['message' => 'Veuillez selectionner une parcelle.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Culture::class,
            'parcelle_choices' => [],
        ]);

        $resolver->setAllowedTypes('parcelle_choices', 'array');
    }
}
