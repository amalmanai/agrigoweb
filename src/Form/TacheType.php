<?php

namespace App\Form;

use App\Entity\Tache;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TacheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClasses = 'py-2.5 px-4 transition duration-200 ease-in-out shadow-sm border border-gray-300 rounded-lg';
        $labelClasses = 'block text-sm font-semibold leading-6 text-gray-900 mb-1';

        $builder
            ->add('tittre_tache', TextType::class, [
                'required' => false,
                'label' => 'Titre de la Tâche',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Ex: Arroser les cultures...'],
            ])
            ->add('description_tache', TextType::class, [
                'required' => false,
                'label' => 'Description',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Détails de la tâche...'],
            ])
            ->add('type_tache', ChoiceType::class, [
                'required' => false,
                'label' => 'Type de Tâche',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
                'placeholder' => 'Sélectionnez un type...',
                'choices' => [
                    'Arrosage' => 'Arrosage',
                    'Fertilisation' => 'Fertilisation',
                    'Désherbage' => 'Désherbage',
                    'Récolte' => 'Récolte',
                    'Semis' => 'Semis',
                    'Paillage' => 'Paillage',
                    'Traitement' => 'Traitement',
                    'Maintenance' => 'Maintenance',
                    'Inspection' => 'Inspection',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('date_tache', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de la Tâche',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
            ])
            ->add('heure_debut_tache', TimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Heure de Début',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
            ])
            ->add('heure_fin_tache', TimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Heure de Fin',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
            ])
            ->add('status_tache', ChoiceType::class, [
                'required' => false,
                'label' => 'Statut',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses],
                'placeholder' => 'Sélectionnez un statut...',
                'choices' => [
                    'En Attente' => 'En attente',
                    'En Cours' => 'En cours',
                    'Terminée' => 'Terminée',
                    'Annulée' => 'Annulée',
                    'Suspendue' => 'Suspendue',
                ],
            ])
            ->add('remarque_tache', TextType::class, [
                'required' => false,
                'label' => 'Remarques',
                'label_attr' => ['class' => $labelClasses],
                'attr' => ['class' => $inputClasses, 'placeholder' => 'Observations supplémentaires...'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tache::class,
        ]);
    }
}
