<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontHomeController extends AbstractController
{
    #[Route('/', name: 'app_front_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/home.html.twig');
    }
}
