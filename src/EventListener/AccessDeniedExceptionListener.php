<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;

class AccessDeniedExceptionListener
{
    public function __construct(private Environment $twig)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Check if it's an access denied exception
        if (!$exception instanceof AccessDeniedException) {
            return;
        }

        $isAdmin = false;
        if ($event->getRequest()->attributes->get('_route')) {
            $route = $event->getRequest()->attributes->get('_route');
            // Check if route is admin route
            $isAdmin = strpos($route, 'admin') !== false;
        }

        // Create response with popup
        $html = $this->twig->render('error/access_denied.html.twig', [
            'isAdmin' => $isAdmin,
            'message' => $this->getAccessDeniedMessage($isAdmin),
        ]);

        $response = new Response($html, Response::HTTP_FORBIDDEN);
        $event->setResponse($response);
    }

    private function getAccessDeniedMessage(bool $isAdmin): string
    {
        if ($isAdmin) {
            return "Vous n'avez pas les droits d'administrateur. Seuls les administrateurs peuvent accéder à cette page.";
        }

        return "Vous n'avez pas les droits d'accès à cette page. Contactez un administrateur pour plus d'informations.";
    }
}
