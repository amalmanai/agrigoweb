<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Parcelle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParcelleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomParcelle', TextType::class, [
                'label' => 'Nom de la Parcelle',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Sidi Bouzid Plot',
                ],
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface',
                'html5' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => '0.01',
                    'step' => '0.01',
                ],
            ])
            ->add('coordonneesGps', TextType::class, [
                'label' => 'Coordonnees GPS',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Cliquez sur la carte pour choisir un point',
                    'readonly' => true,
                    'data-parcelle-gps-input' => '1',
                ],
            ])
            ->add('typeSol', ChoiceType::class, [
                'label' => 'Type de Sol',
                'required' => true,
                'placeholder' => 'Selectionner un type de sol',
                'choices' => [
                    'Argileux' => 'Argileux',
                    'Sableux' => 'Sableux',
                    'Limoneux' => 'Limoneux',
                    'Calcaire' => 'Calcaire',
                    'Humifere' => 'Humifere',
                    'Sablo-limoneux' => 'Sablo-limoneux',
                    'Argilo-limoneux' => 'Argilo-limoneux',
                    'Rocheux' => 'Rocheux',
                    'Salin' => 'Salin',
                    'Volcanique' => 'Volcanique',
                    'Autre' => 'Autre',
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
            'data_class' => Parcelle::class,
        ]);
    }
}
