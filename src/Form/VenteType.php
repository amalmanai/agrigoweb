<?php

namespace App\Form;

use App\Entity\Recolte;
use App\Entity\User;
use App\Entity\Vente;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class VenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $owner = $options['recolte_owner'];
        $inputClasses = 'py-2.5 px-4 transition duration-200 ease-in-out shadow-sm';
        $labelClasses = 'block text-sm font-semibold leading-6 text-gray-900 mb-1';

        $builder
            ->add('description', TextType::class, [
                'label' => 'Description de la vente',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Vente lot de tomates...'],
            ])
            ->add('recolte', EntityType::class, [
                'class' => Recolte::class,
                'choice_label' => 'name',
                'label' => 'Récolte liée',
                'placeholder' => 'Sélectionner une récolte',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
                'query_builder' => function (EntityRepository $r) use ($owner) {
                    $qb = $r->createQueryBuilder('rec')->orderBy('rec.name', 'ASC');
                    if ($owner instanceof User) {
                        $qb->andWhere('rec.userId = :uid')->setParameter('uid', $owner->getIdUser());
                    }

                    return $qb;
                },
            ])
            ->add('saleDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de Vente',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => '0.00'],
            ])
            ->add('buyerName', TextType::class, [
                'label' => 'Nom de l’acheteur',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Jean Dupont'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'label_attr' => ['class' => $labelClasses],
                'choices' => [
                    'Pending' => 'Pending',
                    'Completed' => 'Completed',
                    'Cancelled' => 'Cancelled',
                ],
                'attr' => ['class' => $inputClasses],
            ])
            ->add('deliveryLocation', TextType::class, [
                'label' => 'Adresse de livraison',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => '123 Rue de la Livraison, Tunis'],
                'required' => false,
            ])
            ->add('availableQuantity', NumberType::class, [
                'label' => 'Quantite disponible (marketplace)',
                'required' => false,
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Ex: 100'],
            ])
            ->add('isMarketplaceListing', ChoiceType::class, [
                'label' => 'Publier dans le marketplace',
                'label_attr' => ['class' => $labelClasses],
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'attr' => ['class' => $inputClasses],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vente::class,
            'recolte_owner' => null,
        ]);
        $resolver->setAllowedTypes('recolte_owner', ['null', User::class]);
    }
}
