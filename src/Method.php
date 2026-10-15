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
        
        foreach (Registry::getRoutes() as $entry) {
            foreach ($entry as $route) {
                $uris[] = $route->uri;
            }
        }

        return $uris;
    }

    public function uri() : string
    {
        $url = parse_url(
            $_SERVER['REQUEST_URI'] ?? ''
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
        return Registry::currentRoute();
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
            if (in_array($verb, ['get', 'post', 'put', 'patch', 'delete', 'options'], true)) {
                $resource[] = $this->register($verb, $uri, $action);
            }
        }

        return $resource;
    }

    public function resource(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'    => ['index' => "{resource}/{?id}/{nested}", 'create' => "{resource}/{?id}/{nested}/create", 'show' => "{resource}/{id}/{nested}/{nid}", 'edit' => "{resource}/{id}/{nested}/{nid}/edit"],
            'post'   => ['store' => '{resource}/{?id}/{nested}'],
            'put'    => ['update' => '{resource}/{id}/{nested}/{nid}'],
            'patch'  => ['update' => '{resource}/{id}/{nested}/{nid}'],
            'delete' => ['destroy' => '{resource}/{id}/{nested}/{nid}']
        ]);
    }

    public function apiResource(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'    => ['index' => '{resource}/{?id}/{nested}', 'show' => '{resource}/{id}/{nested}/{nid}'],
            'post'   => ['store' => '{resource}/{?id}/{nested}'],
            'put'    => ['update' => '{resource}/{id}/{nested}/{nid}'],
            'patch'  => ['update' => '{resource}/{id}/{nested}/{nid}'],
            'delete' => ['destroy' => '{resource}/{id}/{nested}/{nid}']
        ]);
    }

    public function singleton(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'   => ['show' => '{resource}', 'edit' => '{resource}/edit'],
            'put'   => ['update' => '{resource}'],
            'patch' => ['update' => '{resource}']
        ]);
    }

    public function apiSingleton(string $resource, string $controller) : Resource
    {
        return $this->__createResource($resource, $controller, [
            'get'   => ['show' => '{resource}'],
            'put'   => ['update' => '{resource}'],
            'patch' => ['update' => '{resource}']
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
