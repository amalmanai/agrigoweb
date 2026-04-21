<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): RedirectResponse
    {
        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('warning', 'Acces refuse: vous n\'avez pas les autorisations necessaires.');
        }

        return new RedirectResponse($this->router->generate('app_front_home'));
    }
}
