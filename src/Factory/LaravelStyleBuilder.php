<?php
namespace Clicalmani\Routing\Factory;

use Clicalmani\Routing\Builder;
use Clicalmani\Routing\BuilderInterface;
use Clicalmani\Routing\Memory;
use Clicalmani\Routing\Parameter;
use Clicalmani\Routing\Route;
use Clicalmani\Routing\Segment;

/**
 * Laravel-style Route Builder
 * 
 * Supports Laravel syntax: {param} and {param?} for parameters
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class LaravelStyleBuilder extends Builder implements BuilderInterface
{
    /**
     * @var string Laravel parameter pattern
     */
    private const PARAM_PATTERN = '/\{([a-zA-Z_][a-zA-Z0-9_]*)(\?)?\}/';

    /**
     * @var string Internal parameter prefix (Tonka style)
     */
    private string $internalPrefix;

    public function __construct()
    {
        $this->internalPrefix = \Clicalmani\Foundation\Support\Facades\Config::route('parameter_prefix') ?: ':';
    }

    /**
     * Convert Laravel-style URI to Tonka-style URI
     * 
     * @param string $uri Laravel-style URI (e.g., /users/{id}/posts/{post_id?})
     * @return string Tonka-style URI (e.g., /users/:id/posts/?:post_id)
     */
    private function convertLaravelToTonka(string $uri): string
    {
        return preg_replace_callback(self::PARAM_PATTERN, function($matches) {
            $paramName = $matches[1];
            $isOptional = isset($matches[2]) && $matches[2] === '?';
            
            // Convert {param} to :param or ?:param for optional
            return ($isOptional ? '?' : '') . $this->internalPrefix . $paramName;
        }, $uri);
    }

    /**
     * Get route sequences from URI
     * 
     * @param string $uri Route uri
     * @return Route 
     */
    public function create(string $uri): Route
    {
        // Convert Laravel syntax to Tonka syntax
        $tonkaUri = $this->convertLaravelToTonka($uri);
        
        $route = new Route;
        $route->setUri($tonkaUri);
        
        foreach (preg_split('/\//', $tonkaUri, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $segment = new Segment;
            $segment->name = $part;
            $route[] = $segment;
        }
        
        return $route;
    }

    /**
     * Match candidate routes
     * 
     * @param string $verb
     * @return Route[] 
     */
    public function matches(string $verb): array
    {
        $candidates = [];
        $clientUri = client_url();
        
        // Gauge - count segments in client URI
        $len = count($this->create($clientUri));
        
        /** @var Route $route */
        foreach (Memory::getRoutesByVerb($verb) as $route) {
            // Skip if length doesn't match or route is custom
            if ($len !== count($route) || $route->isCustom()) {
                continue;
            }
            
            // Check for duplicate routes
            if ($this->isBuilt($route)) {
                throw new \Clicalmani\Routing\Exceptions\DuplicateRouteException($route);
            }
            
            $candidates[] = $route;
        }
        
        return $candidates;
    }

    /**
     * Locate the current route in the candidate routes list
     * 
     * @param Route[] $matches
     * @return ?Route
     */
    public function locate(array $matches): ?Route
    {
        $client = $this->getClientRoute();
        $candidates = [];
        
        foreach ($matches as $route) {
            if ($client->equals($this->mock($route))) {
                $candidates[] = $route;
            }
        }
        
        $parameters = $this->parameters($candidates);
        
        foreach ($candidates as $route) {
            if (!$parameters && $route->getParameters()) {
                continue;
            }
            
            foreach ($parameters as $parameter) {
                if (isset($route[$parameter->position])) {
                    /** @var Segment */
                    $segment = $route[$parameter->position];
                    $segment->value = $parameter->value;
                    $parameter->name = $segment->getName();
                    
                    if (false == $segment->isValid()) {
                        continue 2;
                    }
                }
            }
            
            if ($client->equals($route)) {
                foreach ($parameters as $parameter) {
                    if (isset($route[$parameter->position])) {
                        /** @var Segment */
                        $segment = $route[$parameter->position];
                        $segment->register();
                    }
                }
                
                return $route;
            }
        }
        
        return null;
    }

    /**
     * Mock client route to the given route
     * 
     * @param Route $route
     * @return Route
     */
    public function mock(Route $route): Route
    {
        $client = $this->getClientRoute();
        $parameters = [];
        
        foreach ($client as $index => $segment) {
            if (isset($route[$index])) {
                if (false == $route[$index]->isParameter()) {
                    continue;
                }
                
                if (in_array(
                    $this->internalPrefix . $route[$index]->getName(),
                    $route->diff($client)
                )) {
                    $param = new Parameter;
                    $param->value = $segment->name;
                    $param->position = $index;
                    $parameters[] = $param;
                }
            }
        }
        
        if (empty($parameters)) {
            return $client;
        }
        
        foreach ($parameters as $param) {
            if ($route[$param->position]->isParameter()) {
                /** @var Segment */
                $segment = $route[$param->position];
                $segment->value = $param->value;
            }
        }
        
        return $route;
    }

    /**
     * Build the requested route
     * 
     * @return ?Route
     */
    public function getRoute(): ?Route
    {
        return $this->locate(
            $this->matches(
                \Clicalmani\Foundation\Support\Facades\Route::getClientVerb()
            )
        );
    }

    /**
     * Retrieve route parameters
     * 
     * @param Route[] $candidates
     * @return Parameter[]
     */
    public function parameters(array $candidates): array
    {
        $arr = [];
        $client = $this->getClientRoute();
        
        foreach ($candidates as $match) {
            $arr[] = $match->getSegmentsNames();
        }
        
        $ret = [];
        
        foreach (array_diff($client->getSegmentsNames(), ...$arr) as $key => $value) {
            $param = new Parameter;
            $param->value = $value;
            $param->position = $key;
            $ret[] = $param;
        }
        
        return $ret;
    }

    /**
     * Check if route is already built
     * 
     * @param Route $route
     * @return bool
     */
    public function isBuilt(Route $route): bool
    {
        $routes = Memory::getRoutesByVerb($route->verb);
        
        foreach ($routes as $existing) {
            if ($existing->equals($route)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get client route from current URI
     * 
     * @return Route
     */
    private function getClientRoute(): Route
    {
        $uri = client_url();
        return $this->create($uri);
    }
}