<?php

namespace App\Form;

use App\Entity\Recolte;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecolteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClasses = 'py-2.5 px-4 transition duration-200 ease-in-out shadow-sm';
        $labelClasses = 'block text-sm font-semibold leading-6 text-gray-900 mb-1';

        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Nom du Produit',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Tomates, Blé...'],
            ])
            ->add('harvestDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de Récolte',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
            ])
            ->add('quantity', NumberType::class, [
                'required' => false,
                'label' => 'Quantité',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => '100.5'],
            ])
            ->add('unit', TextType::class, [
                'required' => false,
                'label' => 'Unité',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'kg, tonnes...'],
            ])
            ->add('productionCost', NumberType::class, [
                'required' => false,
                'label' => 'Coût de Production',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Coût en €'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recolte::class,
        ]);
    }
}
