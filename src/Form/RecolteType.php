<?php

namespace App\Form;

use App\Entity\Parcelle;
use App\Entity\Recolte;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecolteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $owner = $options['parcelle_owner'];
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
            ->add('unit', ChoiceType::class, [
                'required' => false,
                'label' => 'Unité',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
                'placeholder' => 'Sélectionnez une unité...',
                'choices' => [
                    // Unités pour Récoltes
                    'Kilogramme (kg)' => 'kg',
                    'Tonne (T)' => 'T',
                    'Gramme (g)' => 'g',
                    'Hectolitre (hl)' => 'hl',
                    'Litre (L)' => 'L',
                    'Millilitre (ml)' => 'ml',
                    
                    // Unités pour Parcelles
                    'Hectare (ha)' => 'ha',
                    'Mètre carré (m²)' => 'm²',
                    'Centimètre (cm)' => 'cm',
                    'Mètre (m)' => 'm',
                    
                    // Unités pour Tâches
                    'Heure (h)' => 'h',
                    'Jour (j)' => 'j',
                    'Semaine (sem)' => 'sem',
                    'Mois' => 'mois',
                    'Minute (mn)' => 'mn',
                    
                    // Autres unités communes
                    'Unité (u)' => 'u',
                    'Paquet' => 'paquet',
                    'Boîte' => 'boîte',
                    'Sac' => 'sac',
                    'Nombre' => 'nombre',
                ],
            ])
            ->add('productionCost', NumberType::class, [
                'required' => false,
                'label' => 'Coût de Production',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Coût en €'],
            ])
            ->add('parcelle', EntityType::class, [
                'class' => Parcelle::class,
                'choice_label' => 'nomParcelle',
                'required' => false,
                'label' => 'Parcelle',
                'placeholder' => 'Sélectionnez une parcelle...',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
                'query_builder' => function (EntityRepository $repository) use ($owner) {
                    $qb = $repository->createQueryBuilder('p')
                        ->orderBy('p.nomParcelle', 'ASC');

                    if ($owner instanceof User) {
                        $qb->andWhere('p.owner = :owner')->setParameter('owner', $owner);
                    }

                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recolte::class,
            'parcelle_owner' => null,
        ]);

        $resolver->setAllowedTypes('parcelle_owner', ['null', User::class]);
    }
}
