<?php
namespace Clicalmani\Routing\Factory;

use Clicalmani\Routing\Builder;
use Clicalmani\Routing\Memory;
use Clicalmani\Routing\Parameter;
use Clicalmani\Routing\Route;
use Clicalmani\Routing\Segment;

class BasicBuilder extends Builder implements \Clicalmani\Routing\BuilderInterface
{
    /**
     * Get route sequences
     * 
     * @param string $uri Route uri
     * @return \Clicalmani\Routing\Route 
     */
    public function create(string $uri) : Route
    {
        $route = new Route;
        $route->setUri($uri);
        
        foreach (preg_split('/\//', $uri, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $segment = new Segment;
            $segment->name = $part;
            $route[] = $segment;
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
        $len = count( $this->create( client_uri() ) );
        
        /** @var \Clicalmani\Routing\Route $route */
        foreach (Memory::getRoutesByVerb($verb) as $route) {

            if ($len !== count($route) OR $route->isCustom()) continue;

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

                /** @var \Clicalmani\Routing\Segment */
                $segment = $route[$parameter->position];

                $segment->value = $parameter->value;
                $parameter->name = $segment->name;
                
                if (FALSE == $segment->isValid()) continue 2;
            }
            
            if ($client->equals($route)) {
                foreach ($parameters as $parameter) {
                    /** @var \Clicalmani\Routing\Segment */
                    $segment = $route[$parameter->position];
                    $segment->register();
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
        
        foreach ($client as $index => $segment) {

            if (FALSE == $route[$index]->isParameter()) continue;
            
            if ( in_array(config('route.parameter_prefix') . $route[$index]->getName(), $route->diff($client)) ) {
                $param = new Parameter;
                $param->value = $segment->name;
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
                /** @var \Clicalmani\Routing\Segment */
                $segment = $route[$param->position];
                $segment->value = $param->value;
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

        foreach ($candidates as $match) $arr[] = $match->getSegmentsNames();

        $ret = [];
        
        foreach (array_diff($client->getSegmentsNames(), ...$arr) as $key => $value) {
            $param = new Parameter;
            $param->value = $value;
            $param->position = $key;
            $ret[] = $param;
        }

        return $ret;
    }
}
