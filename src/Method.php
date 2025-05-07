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
     * Returns registered routes uris.
     * 
     * @return string[]
     */
    public function all() : array
    {
        $uris = [];
        
        foreach (Memory::getRoutes() as $entry) {
            foreach ($entry as $route) {
                $uris[] = $route->uri;
            }
        }

        return $uris;
    }

    /**
     * Returns the client uri.
     * 
     * @return string
     */
    public function uri() : string
    {
        // Do not route in console mode
        if ( isConsoleMode() ) return '@console';
        
        $url = parse_url(
            $_SERVER['REQUEST_URI']
        );

        return isset($url['path']) ? $url['path']: '/';
    }

    /**
     * Return the current route.
     * 
     * @return \Clicalmani\Routing\Route|null
     */
    public function current(): Route|null
    {
        return Memory::currentRoute();
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
     * Match multiple methods
     * 
     * @param array $matches
     * @param string $uri
     * @param mixed $action
     * @return \Clicalmani\Routing\Resource
     */
    public function match(array $matches, string $uri, mixed $action) : Resource
    {
        $resource = new Resource;

        foreach ($matches as $verb) {
            $verb = strtolower($verb);
            if ( array_key_exists($verb, Memory::getRoutes()) ) {
                $validator = $this->register($verb, $uri, $action);
                $resource[] = $validator;
            }
        }

        return $resource;
    }

    /**
     * Resource route
     * 
     * @param string $resource Resource name
     * @param string $controller Resource controller
     * @return \Clicalmani\Routing\Resource
     */
    public function resource(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'    => ['index' => '{id}/{nested}', 'create' => '{id}/{nested}/create', 'show' => '{id}/{nested}/{nid}', 'edit' => '{id}/{nested}/{nid}/edit'],
            'post'   => ['store' => '{id}/{nested}'],
            'put'    => ['update' => '{id}/{nested}/{nid}'],
            'patch'  => ['update' => '{id}/{nested}/{nid}'],
            'delete' => ['destroy' => '{id}/{nested}/{nid}']
        ]);
    }

    /**
     * API resource
     * 
     * @param string $resource
     * @param string $controller Controller class
     * @return \Clicalmani\Routing\Resource
     */
    public function apiResource(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'    => ['index' => '{?id}/{nested}', 'show' => '{id}/{nested}/{nid}'],
            'post'   => ['store' => '{?id}/{nested}'],
            'put'    => ['update' => '{id}/{nested}/{nid}'],
            'patch'  => ['update' => '{id}/{nested}/{nid}'],
            'delete' => ['destroy' => '{id}/{nested}/{nid}']
        ]);
    }

    /**
     * Single resource
     * 
     * @param string $resource
     * @param string $controller
     * @return \Clicalmani\Routing\Resource
     */
    public function singleton(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'    => ['show' => '{id}/{nested}/{nid}', 'edit' => '{id}/{nested}/{nid}/edit'],
            'put'    => ['update' => '{id}/{nested}/{nid}'],
            'patch'  => ['update' => '{id}/{nested}/{nid}']
        ]);
    }

    /**
     * API single resource
     * 
     * @param string $resource
     * @param string $controller
     * @return \Clicalmani\Routing\Resource
     */
    public function apiSingleton(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'    => ['show' => '{id}/{nested}/{nid}'],
            'put'    => ['update' => '{id}/{nested}/{nid}'],
            'patch'  => ['update' => '{id}/{nested}/{nid}'],
        ]);
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
