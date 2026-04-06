<?php

namespace App\Form;

use App\Entity\Parcelle;
use App\Entity\SystemeIrrigation;
use App\Repository\ParcelleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SystemeIrrigationType extends AbstractType
{
    public function __construct(private ParcelleRepository $parcelleRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parcelle', EntityType::class, [
                'class' => Parcelle::class,
                'choice_label' => 'nomParcelle',
                'label' => 'Parcelle',
                'mapped' => false,
                'required' => true,
                'placeholder' => '— Choisir une parcelle —',
                'query_builder' => fn ($r) => $r->createQueryBuilder('x')->orderBy('x.nomParcelle', 'ASC'),
            ])
            ->add('nomSysteme', TextType::class, [
                'label' => 'Nom du système',
                'attr' => ['maxlength' => 100],
            ])
            ->add('seuilHumidite', TextType::class, [
                'label' => 'Seuil d\'humidité (%)',
                'required' => false,
                'attr' => ['inputmode' => 'decimal'],
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'Mode',
                'choices' => ['Automatique' => 'AUTO', 'Manuel' => 'MANUEL'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => ['Actif' => 'ACTIF', 'Inactif' => 'INACTIF'],
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            if (!$data instanceof SystemeIrrigation || !$data->getIdParcelle()) {
                return;
            }
            $parcelle = $this->parcelleRepository->find($data->getIdParcelle());
            if ($parcelle) {
                $event->getForm()->get('parcelle')->setData($parcelle);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SystemeIrrigation::class,
        ]);
    }
}
