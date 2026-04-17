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
            ->add('emailUser', \Symfony\Component\Form\Extension\Core\Type\EmailType::class, [
                'label' => 'E-mail personnel',
                'attr' => ['placeholder' => 'exemple@agri.tn']
            ])
            ->add('numUser', \Symfony\Component\Form\Extension\Core\Type\TelType::class, [
                'label' => 'Numéro de Téléphone',
                'attr' => ['placeholder' => '8 chiffres']
            ])
            ->add('adresseUser', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['placeholder' => 'Votre adresse complète']
            ])
            ->add('photoPath', TextType::class, [
                'label' => 'Photo (URL ou nom de fichier)',
                'required' => false,
                'attr' => ['placeholder' => 'https://... ou photo.jpg'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
