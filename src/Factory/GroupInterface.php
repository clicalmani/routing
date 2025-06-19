<?php
namespace Clicalmani\Routing\Factory;

interface GroupInterface
{
    /**
     * Start grouping
     * 
     * @return void
     */
    public function start() : void;

    /**
     * Stop grouping
     * 
     * @return void
     */
    public function stop() : void;

    /**
     * Verify if grouping is terminated
     * 
     * @return bool
     */
    public function isComplete() : bool;

    /**
     * Controller group
     * 
     * @param ?callable $callback
     * @return self
     */
    public function group(?callable $callback = null) : self;

    /**
     * Run each member of the group
     * 
     * @return self
     */
    public function run() : self;

    /**
     * Prefix group's routes
     * 
     * @param string $prefix
     * @return self
     */
    public function prefix(string $prefix) : self;

    /**
     * Define one or more middlewares on the routes group
     * 
     * @param string|string[] $name_or_classe Middleware name or class
     * @return void
     */
    public function middleware(string|array $name_or_class) : void;

    /**
     * Remove one or more middlewares from the routes group
     * 
     * @param string|string[] $name_or_class Middleware name or class
     * @return void
     */
    public function withoutMiddleware(string|array $name_or_class) : void;

    /**
     * Set parameter pattern. Useful for optional parameters
     * 
     * @param string $param
     * @param string $pattern
     * @return self
     */
    public function pattern(string $param, string $pattern) : self;

    /**
     * Set multiple patterns
     * 
     * @see RouteGroup::patterns()
     * @param string[] $params
     * @param string[] $patters
     * @return self
     */
    public function patterns(array $params, array $patterns) : self;

    /**
     * Validate parameter's value against any validator.
     * 
     * @param string $param
     * @param string $rule
     * @return self
     */
    public function where(string $param, string $rule) : self;

    /**
     * Add group route
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return void
     */
    public function addRoute(\Clicalmani\Routing\Route $route) : void;

    /**
     * Check if group has middleware
     * 
     * @return bool
     */
    public function hasMiddleware() : bool;

    /**
     * Get group middleware
     * 
     * @return string
     */
    public function getMiddleware() : string;

    /**
     * Set group middleware
     * 
     * @param string $middleware
     * @return void
     */
    public function setMiddleware(string $middleware) : void;

    /**
     * Share resources with a sub-group
     * 
     * @param \Clicalmani\Routing\Group $sub
     * @return void
     */
    public function shareResourcesWith(\Clicalmani\Routing\Group $sub) : void;
}