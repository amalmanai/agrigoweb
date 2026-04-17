<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomUser', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => ['placeholder' => 'Entrez votre nom'],
            ])
            ->add('prenomUser', TextType::class, [
                'label' => 'Prenom',
                'required' => false,
                'attr' => ['placeholder' => 'Entrez votre prenom'],
            ])
            ->add('emailUser', \Symfony\Component\Form\Extension\Core\Type\EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['placeholder' => 'exemple@agri.tn']
            ])
            ->add('numUser', \Symfony\Component\Form\Extension\Core\Type\TelType::class, [
                'label' => 'Numéro de Téléphone',
                'required' => false,
                'attr' => ['placeholder' => '8 chiffres']
            ])
            ->add('adresseUser', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['placeholder' => 'Votre adresse complète']
            ])
            ->add('photoPath', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['accept' => 'image/*'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
