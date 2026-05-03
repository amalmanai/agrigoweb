<?php

declare(strict_types=1);

namespace App\Dto;

final class ParcelleSummaryDto
{
    public readonly int $id;
    public readonly string $nomParcelle;
    public readonly float $surface;
    public readonly ?string $coordonneesGps;
    public readonly ?string $typeSol;
    public readonly int $cultureCount;

    public function __construct(
        int|string $id,
        string $nomParcelle,
        float|int|string $surface,
        ?string $coordonneesGps,
        ?string $typeSol,
        int|string $cultureCount
    ) {
        $this->id = (int) $id;
        $this->nomParcelle = $nomParcelle;
        $this->surface = (float) $surface;
        $this->coordonneesGps = $coordonneesGps;
        $this->typeSol = $typeSol;
        $this->cultureCount = (int) $cultureCount;
    }
}
