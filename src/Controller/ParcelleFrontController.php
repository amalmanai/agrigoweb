<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Parcelle;
use App\Repository\ParcelleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParcelleFrontController extends AbstractController
{
    #[Route('/parcelle', name: 'app_parcelle_list', methods: ['GET'])]
    public function list(Request $request, ParcelleRepository $parcelleRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'nomParcelle');
        $direction = (string) $request->query->get('direction', 'ASC');

        return $this->render('front/parcelle/list.html.twig', [
            'parcelles' => $parcelleRepository->findFiltered($search, $sort, $direction),
            'search' => $search,
            'sort' => $sort,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'sortOptions' => [
                'nomParcelle' => 'Nom',
                'surface' => 'Surface',
                'typeSol' => 'Type de sol',
                'coordonneesGps' => 'Coordonnees',
            ],
        ]);
    }

    #[Route('/parcelle/{id}', name: 'app_parcelle_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Parcelle $parcelle): Response
    {
        return $this->render('front/parcelle/show.html.twig', [
            'parcelle' => $parcelle,
        ]);
    }
}
