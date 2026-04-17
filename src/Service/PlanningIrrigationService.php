<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\HistoriqueIrrigation;
use App\Entity\SystemeIrrigation;
use App\Repository\HistoriqueIrrigationRepository;
use App\Repository\SystemeIrrigationRepository;

class PlanningIrrigationService
{
    public function __construct(
        private readonly SystemeIrrigationRepository $systemeRepository,
        private readonly HistoriqueIrrigationRepository $historiqueRepository,
    ) {
    }

    /**
     * @return list<array{id_systeme:int, nom_systeme:string, creneau_suggere:string, volume_estime:float}>
     */
    public function suggestPlanningForParcelle(int $idParcelle): array
    {
        $systemes = $this->systemeRepository->findBy([
            'id_parcelle' => $idParcelle,
            'statut' => 'ACTIF',
        ], [
            'nom_systeme' => 'ASC',
        ]);

        $resumeParSysteme = [];
        foreach ($systemes as $systeme) {
            if (!$systeme instanceof SystemeIrrigation) {
                continue;
            }

            $resumeParSysteme[] = [
                'systeme' => $systeme,
                'resume' => $this->buildSystemeResume($systeme),
            ];
        }

        usort($resumeParSysteme, static function (array $left, array $right): int {
            $dureeCompare = $left['resume']['duree_moyenne'] <=> $right['resume']['duree_moyenne'];
            if (0 !== $dureeCompare) {
                return $dureeCompare;
            }

            return strcmp((string) ($left['systeme']->getNomSysteme() ?? ''), (string) ($right['systeme']->getNomSysteme() ?? ''));
        });

        $planning = [];
        $cursor = new \DateTimeImmutable('today 06:00');

        foreach ($resumeParSysteme as $item) {
            $systeme = $item['systeme'];
            $resume = $item['resume'];
            $duree = max(15, (int) round($resume['duree_moyenne']));
            $debut = $cursor;
            $fin = $debut->modify(sprintf('+%d minutes', $duree));

            $planning[] = [
                'id_systeme' => (int) $systeme->getIdSysteme(),
                'nom_systeme' => (string) ($systeme->getNomSysteme() ?? ''),
                'creneau_suggere' => sprintf('%s - %s', $debut->format('H:i'), $fin->format('H:i')),
                'volume_estime' => $resume['volume_moyen'],
            ];

            $cursor = $fin->modify('+5 minutes');
            if ((int) $cursor->format('H') < 6) {
                $cursor = new \DateTimeImmutable('today 06:00');
            }
        }

        return $planning;
    }

    /**
     * @return array{volume_moyen:float, duree_moyenne:float}
     */
    private function buildSystemeResume(SystemeIrrigation $systeme): array
    {
        $historiques = $this->historiqueRepository->findBy([
            'systemeIrrigation' => $systeme,
        ], [
            'date_irrigation' => 'DESC',
        ], 5);

        return $this->computeAverages($historiques);
    }

    /**
     * @param list<HistoriqueIrrigation> $historiques
     * @return array{volume_moyen:float, duree_moyenne:float}
     */
    private function computeAverages(array $historiques): array
    {
        $volumes = [];
        $durees = [];

        foreach ($historiques as $historique) {
            $volume = $historique->getVolumeEau();
            if (null !== $volume && '' !== $volume) {
                $volumes[] = (float) $volume;
            }

            $duree = $historique->getDureeMinutes();
            if (null !== $duree) {
                $durees[] = (float) $duree;
            }
        }

        $volumeMoyen = [] !== $volumes ? array_sum($volumes) / count($volumes) : 0.0;
        $dureeMoyenne = [] !== $durees ? array_sum($durees) / count($durees) : 20.0;

        return [
            'volume_moyen' => round($volumeMoyen, 2),
            'duree_moyenne' => round($dureeMoyenne, 2),
        ];
    }
}