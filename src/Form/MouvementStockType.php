<?php

namespace App\Form;

use App\Entity\MouvementStock;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MouvementStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('type_mouvement', ChoiceType::class, [
                'label' => 'Type de mouvement',
                'choices' => [
                    'Entrée' => 'Entrée',
                    'Sortie' => 'Sortie',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('date_mouvement', DateType::class, [
                'label' => 'Date du mouvement',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['class' => 'form-control']
            ])
            ->add('motif', TextType::class, [
                'label' => 'Motif',
                'attr' => ['class' => 'form-control']
            ])
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => fn(Produit $produit) => sprintf('%s (ID: %d)', $produit->getNomProduit(), $produit->getIdProduit()),
                'label' => 'Produit',
                'placeholder' => 'Sélectionnez un produit',
                'attr' => ['class' => 'form-select']
            ])
        ;
        
        // We will manually set id_user in the controller if needed or it's hidden, so we won't add it to the user-facing form.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MouvementStock::class,
        ]);
    }
}
