<?php 
namespace Clicalmani\Routing;

/**
 * Route methods trait
 * 
 * @package clicalmani/routes
 * @author @clicalmani
 */
trait Method
{
    /**
     * Returns registered routes signatures.
     * 
     * @return string[]
     */
    public function all() : array
    {
        $signatures = [];
        
        foreach (Cache::getRoutes() as $entry) {
            foreach ($entry as $route) {
                $signatures[] = $route->signature;
            }
        }

        return $signatures;
    }

    /**
     * Returns the current route
     * 
     * @return string
     */
    public function current() : string
    {
        // Do not route in console mode
        if ( inConsoleMode() ) return '@console';
        
        $url = parse_url(
            $_SERVER['REQUEST_URI']
        );

        return isset($url['path']) ? $url['path']: '/';
    }

    /**
     * Method GET
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function get(string $route, mixed $action = null) : Validator|Group
    {
        return $this->register('get', $route, $action);
    }

    /**
     * Method POST
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function post(string $route, mixed $action) : Validator|Group
    {
        return $this->register('post', $route, $action);
    }

    /**
     * Method PATCH
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function patch(string $route, mixed $action) : Validator|Group
    {
        return $this->register('patch', $route, $action);
    }

    /**
     * Method PUT
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function put(string $route, mixed $action) : Validator|Group
    {
        return $this->register('put', $route, $action);
    }

    /**
     * Method OPTIONS
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function options(string $route, mixed $action) : Validator|Group
    {
        return $this->register('options', $route, $action);
    }

    /**
     * Method DELETE
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function delete(string $route, mixed $action) : Validator|Group
    {
        return $this->register('delete', $route, $action);
    }

    /**
     * Any method
     * 
     * @param string $route
     * @param mixed $action
     * @return void
     */
    public function any(string $route, mixed $action) : void
    {
        foreach ($this->getSignatures() as $method => $arr) {
            $this->setRouteSignature($method, $route, $action);
        }
    }

    /**
     * Match multiple methods
     * 
     * @param array $matches
     * @param string $signature
     * @param mixed $action
     * @return \Clicalmani\Routing\Resource
     */
    public function match(array $matches, string $signature, mixed $action) : Resource
    {
        $resource = new Resource;

        foreach ($matches as $verb) {
            $verb = strtolower($verb);
            if ( array_key_exists($verb, Cache::getRoutes()) ) {
                $validator = $this->register($verb, $signature, $action);
                $resource[] = $validator;
            }
        }

        return $resource;
    }

    /**
     * Resource route
     * 
     * @param string $resource
     * @param string $controller
     * @return \Clicalmani\Routing\Resource
     */
    public function resource(string $resource, string $controller = null) : Resource
    {
        $routines = new Resource;

        $routes = [
            'get'    => ['index' => '', 'create' => 'create', 'show' => ':id', 'edit' => ':id/edit'],
            'post'   => ['store' => ''],
            'put'    => ['update' => ':id'],
            'patch'  => ['update' => ':id'],
            'delete' => ['destroy' => ':id']
        ];

        foreach ($routes as $verb => $sigs) {
            foreach ($sigs as $action => $sig) {
                $routines[] = $this->register($verb, $resource . '/' . $sig, [$controller, $action]);
            }
        }

        return $routines;
    }

    /**
     * Multiple resources
     * 
     * @param mixed $resource
     * @return void
     */
    public function resources(mixed $resources) : void
    {
        $routines = new Resource;

        foreach ($resources as $resource => $controller) {
            $this->resource($resource, $controller);
        }
    }

    /**
     * API resource
     * 
     * @param mixed $resource
     * @param ?string $controller Controller class
     * @param ?array $actions Customize actions
     * @return \Clicalmani\Routing\Resource
     */
    public function apiResource(mixed $resource, ?string $controller = null, ?array $actions = []) : Resource
    {
        $routines = new Resource;

        $routes = [
            'get'    => ['index' => '', 'create' => ':id'],
            'post'   => ['store' => ''],
            'put'    => ['update' => ':id'],
            'patch'  => ['update' => ':id'],
            'delete' => ['destroy' => ':id']
        ];

        ( new Group(function() use($routes, $routines, $controller, $actions) {
            foreach ($routes as $method => $sigs) {
                foreach ($sigs as $action => $sig) {
                    
                    if ( !empty($actions) && !in_array($action, $actions) ) continue;
                    /** @var \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group */
                    $return = $this->register($method, $sig, [$controller, $action]);
                    
                    if (get_class($return) === \Clicalmani\Routing\Validator::class) {
                        $routines[] = $return->route;
                    } else {
                        /** @var \Clicalmani\Routing\Route */
                        foreach ($return->routes as $route) {
                            $routines[] = $route;
                        }
                    }
                }
            }
        }) )->prefix($resource);
        
        return $routines;
    }

    /**
     * Multiple resources
     * 
     * @param mixed $resources
     * @return void
     */
    public function apiResources(mixed $resources) : void
    {
        $routines = new Resource;

        foreach ($resources as $resource => $controller) {
            $this->apiResource($resource, $controller);
        }
    }

    /**
     * Controller routes
     * 
     * @param string $class Controller class
     * @return \Clicalmani\Routing\Group
     */
    public function controller(string $class) : Group
    {
        return instance(
            Group::class, 
            fn(Group $instance) => $instance->controller = $class
        );
    }
}
