<?php

declare(strict_types=1);

namespace App\Dto;

final class CultureEtatCountDto
{
    public readonly string $etat;
    public readonly int $total;

    public function __construct(
        string $etat,
        int|string $total
    ) {
        $this->etat = $etat;
        $this->total = (int) $total;
    }
}
