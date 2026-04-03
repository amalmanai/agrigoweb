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

class CultureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomCulture', TextType::class, [
                'label' => 'Nom de la Culture',
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
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('etatCroissance', ChoiceType::class, [
                'label' => 'Etat de Croissance',
                'required' => false,
                'placeholder' => 'Selectionner un etat',
                'choices' => [
                    'Semis' => 'Semis',
                    'Croissance' => 'Croissance',
                    'Floraison' => 'Floraison',
                    'Recolte' => 'Recolte',
                    'Recolte termine' => 'Recolte termine',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('rendementPrevu', NumberType::class, [
                'label' => 'Rendement Prevu',
                'required' => false,
                'scale' => 2,
                'html5' => true,
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
                'choice_label' => 'nomParcelle',
                'required' => false,
                'placeholder' => 'Aucune parcelle',
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
        ]);
    }
}
