<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

trait CrudRoutesTrait
{
    private function crudRoute(Request $request, string $action): string
    {
        $current = (string) $request->attributes->get('_route');
        $base = preg_replace('/_(index|new|edit|show|delete)$/', '', $current);

        return $base.'_'.$action;
    }
}
