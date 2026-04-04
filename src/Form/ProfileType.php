<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomUser', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrez votre nom'],
            ])
            ->add('prenomUser', TextType::class, [
                'label' => 'Prenom',
                'attr' => ['placeholder' => 'Entrez votre prenom'],
            ])
            ->add('photoPath', TextType::class, [
                'label' => 'Photo (URL ou nom de fichier)',
                'required' => false,
                'attr' => ['placeholder' => 'https://... ou photo.jpg'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Laissez vide pour garder le mot de passe actuel',
                ],
                'constraints' => [
                    new Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caracteres',
                        max: 4096
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
