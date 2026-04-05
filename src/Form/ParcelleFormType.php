<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Parcelle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;

class ParcelleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomParcelle', TextType::class, [
                'label' => 'Nom de la Parcelle',
                'trim' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de la parcelle est obligatoire.']),
                    new Length(min: 2, max: 100, minMessage: 'Le nom doit contenir au moins {{ limit }} caracteres.', maxMessage: 'Le nom ne doit pas depasser {{ limit }} caracteres.'),
                    new Regex(pattern: '/^[\p{L}0-9][\p{L}0-9\s\-\']*$/u', message: 'Le nom contient des caracteres invalides.'),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Sidi Bouzid Plot',
                    'maxlength' => 100,
                ],
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface',
                'html5' => true,
                'scale' => 2,
                'constraints' => [
                    new NotBlank(['message' => 'La surface est obligatoire.']),
                    new Positive(['message' => 'La surface doit etre strictement positive.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'min' => '0.01',
                    'max' => '1000000',
                    'step' => '0.01',
                ],
            ])
            ->add('coordonneesGps', TextType::class, [
                'label' => 'Coordonnees GPS',
                'required' => true,
                'trim' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Les coordonnees GPS sont obligatoires.']),
                    new Regex(pattern: '/^\s*-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?\s*$/', message: 'Format invalide. Utilisez: latitude, longitude.'),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 36.80, 10.18',
                    'pattern' => '^\s*-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?\s*$',
                ],
            ])
            ->add('typeSol', TextType::class, [
                'label' => 'Type de Sol',
                'required' => true,
                'trim' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le type de sol est obligatoire.']),
                    new Length(min: 2, max: 50, minMessage: 'Le type de sol doit contenir au moins {{ limit }} caracteres.', maxMessage: 'Le type de sol ne doit pas depasser {{ limit }} caracteres.'),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Argileux',
                    'maxlength' => 50,
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
            'data_class' => Parcelle::class,
        ]);
    }
}
