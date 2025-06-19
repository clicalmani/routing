<?php
namespace Clicalmani\Routing\Factory;

interface RouteMemoryInterface
{
    /**
     * Returns registered routes uris for the specified verb.
     * 
     * @param string $verb
     * @return \Clicalmani\Routing\Route[]
     */
    public static function getRoutesByVerb(string $verb) : array;

    /**
     * Routes getter
     * 
     * @return array
     */
    public static function getRoutes() : array;

    /**
     * Routes setter
     * 
     * @return void
     */
    public static function setRoutes(array $routes) : void;

    /**
     * Add new route
     * 
     * @param \Clicalmani\Routing\Route
     * @return void
     */
    public static function addRoute(\Clicalmani\Routing\Route $route) : void;

    /**
     * Remove a route by verb
     * 
     * @param \Clicalmani\Routing\Route $route
     * @param string $verb
     * @return bool
     */
    public static function removeRoute(\Clicalmani\Routing\Route $route, string $verb) : bool;

    /**
     * Register a global pattern
     * 
     * @param string $param Parameter name
     * @param string $pattern A regular expression pattern without delimiters
     * @return void
     */
    public static function registerPattern(string $param, string $pattern) : void;

    /**
     * Register a global validation constraint.
     * 
     * @param string $param Parameter name
     * @param string $constraint A validation constraint.
     * @return void
     */
    public static function registerConstraint(string $param, string $constraint) : void;

    /**
     * Get global patterns
     * 
     * @return array
     */
    public static function getGlobalPatterns() : array;

    /**
     * Register a route guard
     * 
     * @param string $uid Guard's unique id
     * @param string $param Parameter to guard against
     * @param string $callback Callback function
     * @return void
     */
    public static function addGuard(string $uid, string $param, callable $callback) : void;

    /**
     * Returns a registered guard
     * 
     * @param string $uid Guard unique id
     * @return ?array Route guard on success, or null on failure
     */
    public static function getGuard(string $uid) : ?array;

    /**
     * Gets or sets current route.
     * 
     * @param ?\Clicalmani\Routing\Route $route
     * @return mixed
     */
    public static function currentRoute(?\Clicalmani\Routing\Route $route = null) : mixed;

    /**
     * Gets or sets current group.
     * 
     * @param ?\Clicalmani\Routing\Group $group
     * @return mixed
     */
    public static function currentGroup(?\Clicalmani\Routing\Group $group = null) : mixed;

    /**
     * Start recording
     * 
     * @return void
     */
    public static function startRecording(string $name) : void;

    /**
     * Stop recording
     * 
     * @return void
     */
    public static function stopRecording() : void;

    /**
     * Clear record
     * 
     * @return void
     */
    public static function clearRecord() : void;

    /**
     * Record a route
     * 
     * @param ?\Clicalmani\Routing\Route $route
     * @return mixed
     */
    public static function record(?\Clicalmani\Routing\Route $route = null) : mixed;

    /**
     * Returns recording state
     * 
     * @return bool
     */
    public static function isRecording() : bool;
}