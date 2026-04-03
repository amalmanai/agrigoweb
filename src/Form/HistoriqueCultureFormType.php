<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\HistoriqueCulture;
use App\Entity\Parcelle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HistoriqueCultureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parcelle', EntityType::class, [
                'label' => 'Parcelle',
                'class' => Parcelle::class,
                'choice_label' => 'nomParcelle',
                'required' => false,
                'placeholder' => 'Aucune parcelle',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('ancienneCulture', TextType::class, [
                'label' => 'Ancienne Culture',
                'required' => false,
                'attr' => ['class' => 'form-control', 'maxlength' => 100],
            ])
            ->add('dateRecolteEffective', DateType::class, [
                'label' => 'Date Recolte Effective',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('rendementFinal', NumberType::class, [
                'label' => 'Rendement Final',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => ['class' => 'form-control', 'min' => '0', 'max' => '1000000', 'step' => '0.01'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoriqueCulture::class,
        ]);
    }
}
