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
     * @return ?\Clicalmani\Routing\Route
     */
    public function current(): ?Route
    {
        return Memory::currentRoute();
    }

    public function get(string $route, mixed $action = null) : Validator|Group
    {
        return $this->register('get', $route, $action);
    }

    public function post(string $route, mixed $action) : Validator|Group
    {
        return $this->register('post', $route, $action);
    }

    public function patch(string $route, mixed $action) : Validator|Group
    {
        return $this->register('patch', $route, $action);
    }

    public function put(string $route, mixed $action) : Validator|Group
    {
        return $this->register('put', $route, $action);
    }

    public function options(string $route, mixed $action) : Validator|Group
    {
        return $this->register('options', $route, $action);
    }

    public function delete(string $route, mixed $action) : Validator|Group
    {
        return $this->register('delete', $route, $action);
    }

    public function match(array $matches, string $uri, mixed $action) : Resource
    {
        $resource = new Resource;

        foreach ($matches as $verb) {
            $verb = strtolower($verb);
            if ( array_key_exists($verb, Memory::getRoutes()) ) {
                $resource[] = $this->register($verb, $uri, $action);
            }
        }

        return $resource;
    }

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

    public function singleton(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'    => ['show' => '{id}/{nested}/{nid}', 'edit' => '{id}/{nested}/{nid}/edit'],
            'put'    => ['update' => '{id}/{nested}/{nid}'],
            'patch'  => ['update' => '{id}/{nested}/{nid}']
        ]);
    }

    public function apiSingleton(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'    => ['show' => '{id}/{nested}/{nid}'],
            'put'    => ['update' => '{id}/{nested}/{nid}'],
            'patch'  => ['update' => '{id}/{nested}/{nid}'],
        ]);
    }

    public function controller(string $class) : Group
    {
        return instance(
            Group::class, 
            fn(Group $instance) => $instance->controller = $class
        );
    }
}
