<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\HistoriqueIrrigation;
use App\Entity\SystemeIrrigation;
use App\Repository\HistoriqueIrrigationRepository;
use App\Repository\SystemeIrrigationRepository;

class AnomalieIrrigationService
{
    public function __construct(
        private readonly SystemeIrrigationRepository $systemeRepository,
        private readonly HistoriqueIrrigationRepository $historiqueRepository,
    ) {
    }

    /**
     * @param list<int> $idParcelles
     * @return list<array{id_systeme:int, nom_systeme:string, volume_moyen:float, volume_dernier:float, ecart_pourcentage:float, type_anomalie:string}>
     */
    public function detectAnomaliesForParcelles(array $idParcelles): array
    {
        if ($idParcelles === []) {
            return [];
        }

        $systemes = $this->systemeRepository->findBy([
            'statut' => 'ACTIF',
            'id_parcelle' => $idParcelles,
        ], [
            'nom_systeme' => 'ASC',
        ]);

        $anomalies = [];

        foreach ($systemes as $systeme) {
            if (!$systeme instanceof SystemeIrrigation) {
                continue;
            }

            $historiques = $this->historiqueRepository->findBy([
                'systemeIrrigation' => $systeme,
            ], [
                'date_irrigation' => 'DESC',
            ], 10);

            $resume = $this->computeSummary($historiques);
            if (null === $resume) {
                continue;
            }

            $ecart = 0.0 === $resume['volume_moyen']
                ? 0.0
                : (($resume['volume_dernier'] - $resume['volume_moyen']) / $resume['volume_moyen']) * 100;

            if (abs($ecart) <= 30.0) {
                continue;
            }

            $typeAnomalie = $ecart > 0
                ? 'sur-irrigation'
                : 'sous-irrigation ou fuite';

            $anomalies[] = [
                'id_systeme' => (int) $systeme->getIdSysteme(),
                'nom_systeme' => (string) ($systeme->getNomSysteme() ?? ''),
                'volume_moyen' => $resume['volume_moyen'],
                'volume_dernier' => $resume['volume_dernier'],
                'ecart_pourcentage' => round($ecart, 2),
                'type_anomalie' => $typeAnomalie,
            ];
        }

        return $anomalies;
    }

    /**
     * @param list<HistoriqueIrrigation> $historiques
     * @return array{volume_moyen:float, volume_dernier:float}|null
     */
    private function computeSummary(array $historiques): ?array
    {
        if ($historiques === []) {
            return null;
        }

        $volumes = [];
        foreach ($historiques as $historique) {
            $volume = $historique->getVolumeEau();
            if (null !== $volume && '' !== $volume) {
                $volumes[] = (float) $volume;
            }
        }

        if ($volumes === []) {
            return null;
        }

        $dernier = $historiques[0]->getVolumeEau();
        if (null === $dernier || '' === $dernier) {
            return null;
        }

        return [
            'volume_moyen' => round(array_sum($volumes) / count($volumes), 2),
            'volume_dernier' => round((float) $dernier, 2),
        ];
    }
}