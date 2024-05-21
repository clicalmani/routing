<?php
namespace Clicalmani\Routing;

use App\Providers\RouteServiceProvider;

class Builder
{
    /**
     * Get route sequences
     * 
     * @param string $signature Route signature
     * @return \Clicalmani\Routing\Route 
     */
    public function create(string $signature) : Route
    {
        $route = new Route;
        $route->setSignature($signature);
        
        foreach (preg_split('/\//', $signature, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $path = new Path;
            $path->name = $part;
            $route[] = $path;
        }
        
        return $route;
    }

    /**
     * Sort registered routes.
     * 
     * @param string $verb
     * @return array 
     */
    public function sort(string $verb) : array
    {
        /**
         * Sorted routes
         * 
         * @var array
         */
        $sorted_routes = [];
        
        // Gauge
        $len = count( $this->create( current_route() ) );
        
        foreach (Cache::getRoutesByVerb($verb) as $route) {
            
            if ($len !== count($route)) continue;

            $sorted_routes[] = $route;

            if (\Clicalmani\Fundation\Routing\Route::routeExists($route)) throw new \Exception(
                sprintf("Duplicate route %s", $route->getSignature())
            );
        }
        
        return $sorted_routes;
    }

    /**
     * Locate the current route in the sorted routes list.
     * 
     * @param \Clicalmani\Routing\Route[] $sorted
     * @return \Clicalmani\Routing\Route|null
     */
    public function locate(array $sorted) : Route|null
    {
        /**
         * Client route
         * 
         * @var \Clicalmani\Routing\Route
         */
        $client = $this->create( current_route() );
        
        /**
         * Matches of the client route from sorted routes.
         * 
         * @var \Clicalmani\Routing\Route[]
         */
        $matches = [];
        
        foreach ($sorted as $route) {
            
            if ( $client->equals( $this->mock($route) ) ) {
                $matches[] = $route;
            }
        }
        
        $parameters = $this->parameters($client, $matches);
        
        foreach ($matches as $route) {

            if (!$parameters && $route->getParameters()) continue;
            
            foreach ($parameters as $parameter) {

                /** @var \Clicalmani\Routing\Path */
                $path = $route[$parameter->position];

                $path->value = $parameter->value;
                $parameter->name = $path->name;
                
                if (FALSE == $path->isValid()) continue 2;
            }
            
            if ($client->equals($route)) {
                foreach ($parameters as $parameter) {
                    /** @var \Clicalmani\Routing\Path */
                    $path = $route[$parameter->position];
                    $path->register();
                }
                
                return $route;
            }
        }
        
        return null;
    }

    /**
     * Mock client route to the given route.
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return \Clicalmani\Routing\Route 
     */
    public function mock(Route $route) : Route
    {
        /**
         * Client route
         * 
         * @var \Clicalmani\Routing\Route
         */
        $client = $this->create( current_route() );

        /**
         * Client route's parameters
         * 
         * @var \Clicalmani\Routing\Parameter[]
         */
        $parameters = [];
        
        foreach ($client as $index => $path) {

            if (FALSE == $route[$index]->isParameter()) continue;
            
            if ( in_array(':' . $route[$index]->getName(), $route->diff($client)) ) {
                $param = new Parameter;
                $param->value = $path->name;
                $param->position = $index;
                $parameters[] = $param;
            }
        }
        
        /**
         * If there is no parameters, client route should satify the request.
         */
        if (empty($parameters)) return $client;
        
        foreach ($parameters as $param) {
            if (preg_match('/^:/', $route[$param->position]->name)) {
                /** @var \Clicalmani\Routing\Path */
                $path = $route[$param->position];
                $path->value = $param->value;
            }
        }
        
        return $route;
    }

    /**
     * Retrieve route parameters
     * 
     * @param \Clicalmani\Routing\Route $route
     * @param \Clicalmani\Routing\Route[] $matches
     * @return \Clicalmani\Routing\Parameter[]
     */
    public function parameters(Route $route, array $matches) : array
    {
        $arr = [];

        foreach ($matches as $match) $arr[] = $match->getPathNameArray();

        $ret = [];
        
        foreach (array_diff($route->getPathNameArray(), ...$arr) as $key => $value) {
            $param = new Parameter;
            $param->value = $value;
            $param->position = $key;
            $ret[] = $param;
        }

        return $ret;
    }

    /**
     * Capture the request route, verify its existance and its validity. 
     * 
     * @return \Clicalmani\Routing\Route|null
     */
    public function build() : Route|null
    {
        $route = $this->locate( 
            $this->sort( 
                \Clicalmani\Fundation\Routing\Route::getClientVerb()
            ) 
        );
        
        // Run before navigation hook
        if ($hook = $route?->beforeHook()) return $hook( $route );

        // Fire TPS
        RouteServiceProvider::fireTPS($route);
        
        return $route;
    }
}
