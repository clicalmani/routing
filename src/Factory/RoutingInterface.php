<?php
namespace Clicalmani\Routing\Factory;

interface RoutingInterface
{
    /**
     * Returns client's verb
     * 
     * @return string
     */
    public function getClientVerb() : string;

    /**
     * Get client's gateway
     * 
     * @return string
     */
    public function gateway() : string;

    /**
     * Detect api gateway
     * 
     * @return bool
     */
    public function isApi() : bool;

    /**
     * Group routes under a common prefix or middleware
     */
    public function group(mixed ...$parameters) : ?\Clicalmani\Routing\Group;

    /**
     * Attach a middleware
     * 
     * @param string $name_or_class
     * @param mixed $callback If omitted the middleware will be considered as an inline middleware
     * @return \Clicalmani\Routing\Group
     */
    public function middleware(string $name_or_class, mixed $callback = null) : \Clicalmani\Routing\Group;

    /**
     * Set a global pattern
     * 
     * @param string $param Parameter name
     * @param string $pattern A regular expression pattern without delimiters
     * @return void
     */
    public function pattern(string $param, string $pattern): void;

    /**
     * Set a global validation constraint.
     * 
     * @param string $param Parameter name.
     * @param string $constraint A validation constraint.
     * @return void
     */
    public function validate(string $param, string $constraint): void;

    /**
     * Is grouping
     * 
     * @return bool
     */
    public function isGrouping() : bool;

    /**
     * Verify if route exists
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return bool
     */
    public function routeExists(\Clicalmani\Routing\Route $route) : bool;

    /**
     * Revolve a named route. 
     * 
     * @param mixed ...$params 
     * @return mixed
     */
    public function resolve(mixed ...$params) : mixed;

    /**
     * Find a route by name
     * 
     * @param string $name Route name
     * @return ?\Clicalmani\Routing\Route
     */
    public function findByName(string $name) : ?\Clicalmani\Routing\Route;

    /**
     * Returns registered routes uris.
     * 
     * @return string[]
     */
    public function all() : array;

    /**
     * Returns the client uri.
     * 
     * @return string
     */
    public function uri() : string;

    /**
     * Return the current route.
     * 
     * @return ?\Clicalmani\Routing\Route
     */
    public function current(): ?\Clicalmani\Routing\Route;

    /**
     * Method GET
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function get(string $route, mixed $action = null) : \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group;

    /**
     * Method POST
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function post(string $route, mixed $action) : \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group;

    /**
     * Method PATCH
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function patch(string $route, mixed $action) : \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group;

    /**
     * Method PUT
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function put(string $route, mixed $action) : \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group;

    /**
     * Method OPTIONS
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function options(string $route, mixed $action) : \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group;

    /**
     * Method DELETE
     * 
     * @param string $route
     * @param mixed $action
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    public function delete(string $route, mixed $action) : \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group;

    /**
     * Match multiple methods
     * 
     * @param array $matches
     * @param string $uri
     * @param mixed $action
     * @return \Clicalmani\Routing\Resource
     */
    public function match(array $matches, string $uri, mixed $action) : \Clicalmani\Routing\Resource;

    /**
     * Resource route
     * 
     * @param string $resource Resource name
     * @param string $controller Resource controller
     * @return \Clicalmani\Routing\Resource
     */
    public function resource(string $resource, string $controller) : \Clicalmani\Routing\Resource;

    /**
     * API resource
     * 
     * @param string $resource
     * @param string $controller Controller class
     * @return \Clicalmani\Routing\Resource
     */
    public function apiResource(string $resource, string $controller) : \Clicalmani\Routing\Resource;

    /**
     * Single resource
     * 
     * @param string $resource
     * @param string $controller
     * @return \Clicalmani\Routing\Resource
     */
    public function singleton(string $resource, string $controller) : \Clicalmani\Routing\Resource;

    /**
     * API single resource
     * 
     * @param string $resource
     * @param string $controller
     * @return \Clicalmani\Routing\Resource
     */
    public function apiSingleton(string $resource, string $controller) : \Clicalmani\Routing\Resource;

    /**
     * Controller routes
     * 
     * @param string $class Controller class
     * @return \Clicalmani\Routing\Group
     */
    public function controller(string $class) : \Clicalmani\Routing\Group;
}