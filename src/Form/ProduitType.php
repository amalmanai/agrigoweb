<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
            ->add('date_expiration', DateType::class, [
                'label' => 'Date d\'expiration',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('prix_unitaire', IntegerType::class, [
                'label' => 'Prix Unitaire',
                'attr' => ['class' => 'form-control']
            ])
        ;

        if ($options['include_commentaire']) {
            $builder->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Ajoutez un commentaire si nécessaire...'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            'include_commentaire' => true,
        ]);

        $resolver->setAllowedTypes('include_commentaire', 'bool');
    }
}
