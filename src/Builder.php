<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Support\Facades\Config;

/**
 * Builder Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
abstract class Builder
{
    /**
     * Create route
     * 
     * @param string $signature Route signature
     * @return \Clicalmani\Routing\Route 
     */
    abstract public function create(string $uri) : Route;

    /**
     * Check if route is already built.
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return bool
     */
    protected function isBuilt(Route $route) : bool
    {
        $count = 0;

        /** @var \Clicalmani\Routing\Route */
        foreach (Cache::getRoutesByVerb($route->verb) as $r) {
            if ($r->signature === $route->signature) $count++;
        }

        return $count > 1;
    }

    /**
     * Retrieve client route
     * 
     * @return \Clicalmani\Routing\Route
     */
    protected function getClientRoute() : Route
    {
        /**
         * Client route
         * 
         * @var \Clicalmani\Routing\Route
         */
        return $this->create( current_route() );
    }

    /**
     * Build the requested route.
     * 
     * @param string $signature Route signature
     * @return \Clicalmani\Routing\Parameter 
     */
    public static function build() : Route|null
    {
        $builders = Config::route('builders');
        $default_builder = Config::route('default_builder');

        $route = (new $default_builder)->getRoute();
        
        if (!$route) {
            foreach ($builders as $builder) {
                $builder = new $builder;
                $route = $builder->getRoute();
                if ($route) break;
            }
        }
        
        // Run before navigation hook
        if ($hook = $route?->beforeHook()) return $hook( $route );

        // Fire TPS
        \App\Providers\RouteServiceProvider::fireTPS($route);

        return $route;
    }
}
