<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestDoctorController extends AbstractController
{
    #[Route('/test-doctor', name: 'test_doctor')]
    public function index(UserRepository $userRepository): Response
    {
        // On récupère tous les utilisateurs
        // Si le leftJoin/addSelect est absent dans UserRepository, cela va créer un N+1.
        $users = $userRepository->findByAdvancedFilters('', '', '', 'idUser', 'DESC');
        
        $output = "<h1>Test Doctrine Doctor</h1><ul>";
        foreach ($users as $user) {
            // Déclenche l'appel à la collection pour forcer la requête si non jointe
            $parcellesCount = count($user->getParcelles());
            $output .= "<li>User " . $user->getIdUser() . " a " . $parcellesCount . " parcelles.</li>";
        }
        $output .= "</ul>";

        // Retourner une réponse HTML simple. Le WebProfiler va s'injecter automatiquement.
        return new Response('<html><body>' . $output . '</body></html>');
    }
}
