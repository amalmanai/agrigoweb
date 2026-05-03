<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class ProfileCompletionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private RouterInterface $router
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');

        // Routes to ignore
        $ignoredRoutes = [
            'app_profile_edit',
            'app_logout',
            '_wdt',
            '_profiler',
        ];

        if (in_array($route, $ignoredRoutes, true) || str_starts_with($route, 'connect_')) {
            return;
        }

        $user = $this->security->getUser();

        if ($user instanceof User && $user->getNumUser() === 0) {
            // Flash message to inform the user
            $session = $request->hasSession() ? $request->getSession() : null;
            if ($session instanceof \Symfony\Component\HttpFoundation\Session\Flash\FlashBagAwareSessionInterface) {
                /** @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagAwareSessionInterface $session */
                if (!$session->getFlashBag()->has('warning')) {
                    $session->getFlashBag()->add('warning', 'Veuillez compléter votre profil pour continuer.');
                }
            }

            $event->setResponse(new RedirectResponse($this->router->generate('app_profile_edit')));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
