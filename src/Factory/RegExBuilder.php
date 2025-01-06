<?php
namespace Clicalmani\Routing\Factory;

use Clicalmani\Routing\Route;

class RegExBuilder extends \Clicalmani\Routing\Builder implements \Clicalmani\Routing\BuilderInterface
{
    /**
     * Client route
     * 
     * @var \Clicalmani\Routing\Route
     */
    private Route $client;

    /**
     * Create a new route.
     * 
     * @param string $uri Route uri
     * @return \Clicalmani\Routing\Route
     */
    public function create(string $uri) : \Clicalmani\Routing\Route
    {
        $route = new \Clicalmani\Routing\Route;
        $route->setUri($uri);
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
        $this->client = $this->getClientRoute();

        foreach (\Clicalmani\Routing\Memory::getRoutesByVerb($verb) as $route) {
            if ($route->uri && !preg_match("/{$this->sanitizeUri($route->uri)}/", trim($this->client->uri, ' /'))) continue;

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
        return array_pop($matches);
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
     * Sanitize uri.
     * 
     * @return string
     */
    private function sanitizeUri(string $uri) : string
    {
        return rtrim(ltrim(preg_replace('/[\/]/', '\\/', trim($uri, ' /')), '{'), '}');
    }
}
