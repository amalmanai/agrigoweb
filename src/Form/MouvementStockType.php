<?php

namespace App\Form;

use App\Entity\MouvementStock;
use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MouvementStockType extends AbstractType
{
    private ProduitRepository $produitRepository;

    public function __construct(ProduitRepository $produitRepository)
    {
        $this->produitRepository = $produitRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $produits = $this->produitRepository->findAll();
        $produitChoices = [];
        foreach ($produits as $p) {
            $produitChoices[$p->getNomProduit() . ' (ID: ' . $p->getIdProduit() . ')'] = $p->getIdProduit();
        }

        $builder
            ->add('type_mouvement', ChoiceType::class, [
                'label' => 'Type de mouvement',
                'choices' => [
                    'Entrée' => 'Entrée',
                    'Sortie' => 'Sortie',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('date_mouvement', TextType::class, [
                'label' => 'Date du mouvement',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 2025-12-31'
                ]
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['class' => 'form-control']
            ])
            ->add('motif', TextType::class, [
                'label' => 'Motif',
                'attr' => ['class' => 'form-control']
            ])
            ->add('id_produit', ChoiceType::class, [
                'label' => 'Produit',
                'choices' => $produitChoices,
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
