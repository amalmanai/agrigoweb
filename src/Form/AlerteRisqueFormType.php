<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AlerteRisque;
use App\Entity\Culture;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlerteRisqueFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeAlerte', TextType::class, [
                'label' => 'Type Alerte',
                'required' => false,
                'attr' => ['class' => 'form-control', 'maxlength' => 50],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('dateAlerte', DateTimeType::class, [
                'label' => 'Date Alerte',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('culture', EntityType::class, [
                'label' => 'Culture',
                'class' => Culture::class,
                'choice_label' => 'nomCulture',
                'required' => false,
                'placeholder' => 'Aucune culture',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AlerteRisque::class,
        ]);
    }
}
