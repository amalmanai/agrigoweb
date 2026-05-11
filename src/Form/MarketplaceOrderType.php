<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\MarketplaceOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarketplaceOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxQuantity = $options['max_quantity'];

        $builder
            ->add('quantity', NumberType::class, [
                'label' => 'Quantite a commander',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0.01,
                    'step' => 0.01,
                    'max' => $maxQuantity > 0 ? $maxQuantity : null,
                    'placeholder' => 'Ex: 10',
                    'class' => 'form-control',
                ],
            ])
            ->add('deliveryAddress', TextType::class, [
                'label' => 'Adresse de livraison',
                'required' => false,
                'attr' => [
                    'placeholder' => '123 Rue de la Livraison, Tunis',
                    'class' => 'form-control',
                ],
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Instructions de livraison ou details supplementaires',
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarketplaceOrder::class,
            'max_quantity' => 0,
        ]);

        $resolver->setAllowedTypes('max_quantity', ['int', 'float']);
    }
}
