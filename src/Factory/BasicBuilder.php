<?php
namespace Clicalmani\Routing\Factory;

use Clicalmani\Routing\Builder;
use Clicalmani\Routing\Cache;
use Clicalmani\Routing\Parameter;
use Clicalmani\Routing\Path;
use Clicalmani\Routing\Route;

class BasicBuilder extends Builder implements \Clicalmani\Routing\BuilderInterface
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
     * Match candidate routes.
     * 
     * @param string $verb
     * @return \Clicalmani\Routing\Route[] 
     */
    public function matches(string $verb) : array
    {
        /**
         * @var array
         */
        $candidates = [];
        
        // Gauge
        $len = count( $this->create( current_route() ) );
        
        foreach (Cache::getRoutesByVerb($verb) as $route) {
            
            if ($len !== count($route)) continue;

            if ($this->isBuilt($route)) 
                throw new \Clicalmani\Routing\Exceptions\DuplicateRouteException($route);

            $candidates[] = $route;
        }
        
        return $candidates;
    }

    /**
     * Locate the current route in the candidate routes list.
     * 
     * @param \Clicalmani\Routing\Route[] $matches
     * @return \Clicalmani\Routing\Route|null
     */
    public function locate(array $matches) : \Clicalmani\Routing\Route|null
    {
        /**
         * Client route
         * 
         * @var \Clicalmani\Routing\Route
         */
        $client = $this->getClientRoute();
        
        /**
         * Candidate routes.
         * 
         * @var \Clicalmani\Routing\Route[]
         */
        $candidates = [];
        
        foreach ($matches as $route) {
            
            if ( $client->equals( $this->mock($route) ) ) {
                $candidates[] = $route;
            }
        }
        
        $parameters = $this->parameters($candidates);
        
        foreach ($candidates as $route) {
            
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
    public function mock(\Clicalmani\Routing\Route $route) : \Clicalmani\Routing\Route
    {
        /**
         * Client route
         * 
         * @var \Clicalmani\Routing\Route
         */
        $client = $this->getClientRoute();

        /**
         * Client route's parameters
         * 
         * @var \Clicalmani\Routing\Parameter[]
         */
        $parameters = [];
        
        foreach ($client as $index => $path) {

            if (FALSE == $route[$index]->isParameter()) continue;
            
            if ( in_array(config('route.parameter_prefix') . $route[$index]->getName(), $route->diff($client)) ) {
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
            if ($route[$param->position]->isParameter()) {
                /** @var \Clicalmani\Routing\Path */
                $path = $route[$param->position];
                $path->value = $param->value;
            }
        }
        
        return $route;
    }

    /**
     * Build the requested route. 
     * 
     * @return \Clicalmani\Routing\Route|null
     */
    public function getRoute() : \Clicalmani\Routing\Route|null
    {
        return $this->locate(
            $this->matches( 
                \Clicalmani\Foundation\Routing\Route::getClientVerb()
            ) 
        );
    }

    /**
     * Retrieve route parameters
     * 
     * @param \Clicalmani\Routing\Route[] $candidates
     * @return \Clicalmani\Routing\Parameter[]
     */
    public function parameters(array $candidates) : array
    {
        $arr = [];
        $client = $this->getClientRoute();

        foreach ($candidates as $match) $arr[] = $match->getPathNameArray();

        $ret = [];
        
        foreach (array_diff($client->getPathNameArray(), ...$arr) as $key => $value) {
            $param = new Parameter;
            $param->value = $value;
            $param->position = $key;
            $ret[] = $param;
        }

        return $ret;
    }
}
