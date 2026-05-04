<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Culture;
use App\Entity\Parcelle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class CultureFormType extends AbstractType
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw new \LogicException('L\'utilisateur doit être connecté.');
        }

        $builder
            ->add('nomCulture', ChoiceType::class, [
                'label' => 'Nom de la Culture',
                'placeholder' => 'Selectionner une culture',
                'choices' => [
                    'Ble' => 'Ble',
                    'Orge' => 'Orge',
                    'Mais' => 'Mais',
                    'Tomate' => 'Tomate',
                    'Pomme de terre' => 'Pomme de terre',
                    'Poivron' => 'Poivron',
                    'Oignon' => 'Oignon',
                    'Ail' => 'Ail',
                    'Laitue' => 'Laitue',
                    'Concombre' => 'Concombre',
                    'Haricot vert' => 'Haricot vert',
                    'Courgette' => 'Courgette',
                    'Carotte' => 'Carotte',
                    'Fraise' => 'Fraise',
                    'Melon' => 'Melon',
                    'Pasteque' => 'Pasteque',
                    'Olivier' => 'Olivier',
                    'Vigne' => 'Vigne',
                    'Agrumes' => 'Agrumes',
                    'Autre' => 'Autre',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('dateSemis', DateType::class, [
                'label' => 'Date de Semis',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('etatCroissance', ChoiceType::class, [
                'label' => 'Etat de Croissance',
                'required' => true,
                'placeholder' => 'Selectionner un etat',
                'choices' => [
                    'Semis' => 'Semis',
                    'Croissance' => 'Croissance',
                    'Floraison' => 'Floraison',
                    'Recolte' => 'Recolte',
                    'Recolte termine' => 'Recolte termine',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('rendementPrevu', NumberType::class, [
                'label' => 'Rendement Prevu',
                'required' => true,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => '0',
                    'max' => '1000000',
                    'step' => '0.01',
                ],
            ])
            ->add('parcelle', EntityType::class, [
                'label' => 'Parcelle',
                'class' => Parcelle::class,
                'choice_label' => 'nomParcelle',
                'required' => true,
                'placeholder' => 'Selectionner une parcelle',
                'query_builder' => $options['parcelle_query_builder'],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image de la culture',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Parcelle Nord',
                    'list' => 'parcelle-datalist',
                    'autocomplete' => 'off',
                ],
                'invalid_message' => 'Cette parcelle n\'existe pas ou ne vous appartient pas.',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-primary w-100 mt-3',
                ],
            ]);

        $builder->get('parcelle')
            ->addModelTransformer(new \App\Form\DataTransformer\ParcelleToNameTransformer($this->entityManager, $user));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Culture::class,
            'parcelle_query_builder' => null,
        ]);

        $resolver->setAllowedTypes('parcelle_query_builder', ['null', 'callable']);
    }
}
