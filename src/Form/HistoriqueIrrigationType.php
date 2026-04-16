<?php

namespace App\Form;

use App\Entity\HistoriqueIrrigation;
use App\Entity\Parcelle;
use App\Entity\SystemeIrrigation;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HistoriqueIrrigationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $systemeOwner = $options['systeme_owner'];
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }
            foreach (['volumeEau', 'humiditeAvant'] as $field) {
                if (\array_key_exists($field, $data) && $data[$field] === '') {
                    $data[$field] = null;
                }
            }
            $event->setData($data);
        });

        $builder
            ->add('systemeIrrigation', EntityType::class, [
                'class' => SystemeIrrigation::class,
                'choice_label' => fn (SystemeIrrigation $s) => $s->getNomSysteme() ?? '',
                'label' => 'Système d\'irrigation',
                'placeholder' => '— Choisir —',
                'required' => true,
                'query_builder' => function (EntityRepository $r) use ($systemeOwner) {
                    $qb = $r->createQueryBuilder('s')
                        ->innerJoin(Parcelle::class, 'p', 'WITH', 'p.id = s.id_parcelle')
                        ->orderBy('s.nom_systeme', 'ASC');
                    if ($systemeOwner instanceof User) {
                        $qb->andWhere('p.owner = :o')->setParameter('o', $systemeOwner);
                    }

                    return $qb;
                },
            ])
            ->add('dateIrrigation', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'input' => 'datetime',
                'html5' => true,
            ])
            ->add('dureeMinutes', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr' => ['min' => 1, 'max' => 1440],
            ])
            ->add('volumeEau', TextType::class, [
                'label' => 'Volume d\'eau',
                'required' => false,
                'attr' => ['inputmode' => 'decimal'],
            ])
            ->add('humiditeAvant', TextType::class, [
                'label' => 'Humidité avant (%)',
                'required' => false,
                'attr' => ['inputmode' => 'decimal'],
            ])
            ->add('typeDeclenchement', ChoiceType::class, [
                'label' => 'Déclenchement',
                'choices' => ['Automatique' => 'AUTO', 'Manuel' => 'MANUEL'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoriqueIrrigation::class,
            'systeme_owner' => null,
        ]);
        $resolver->setAllowedTypes('systeme_owner', ['null', User::class]);
    }
}
