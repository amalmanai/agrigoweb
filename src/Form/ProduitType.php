<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_produit', TextType::class, [
                'label' => 'Nom du Produit',
                'attr' => ['class' => 'form-control']
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'attr' => ['class' => 'form-control']
            ])
            ->add('quantite_disponible', IntegerType::class, [
                'label' => 'Quantité Disponible',
                'attr' => ['class' => 'form-control']
            ])
            ->add('unite', TextType::class, [
                'label' => 'Unité (ex: kg, L, etc.)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('seuil_alerte', IntegerType::class, [
                'label' => 'Seuil d\'alerte',
                'attr' => ['class' => 'form-control']
            ])
            ->add('date_expiration', TextType::class, [
                'label' => 'Date d\'expiration',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 2025-12-31'
                ]
            ])
            ->add('prix_unitaire', IntegerType::class, [
                'label' => 'Prix Unitaire',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
