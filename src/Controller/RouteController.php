<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Attribute\Route;

class RouteController extends AbstractController
{
    #[Route('/routes', name: 'route_list')]
    public function listRoutes(RouterInterface $router): Response
    {
        $collection = $router->getRouteCollection();
        $routes = [];

        foreach ($collection->all() as $name => $route) {
            $routes[] = [
                'name' => $name,
                'path' => $route->getPath(),
                'methods' => implode(', ', $route->getMethods()),
            ];
        }

        // sort by name for easier browsing
        usort($routes, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $this->render('frontOffice/routes.html.twig', [
            'routes' => $routes,
        ]);
    }
}
