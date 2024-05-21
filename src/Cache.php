<?php
namespace Clicalmani\Routing;

/**
 * Cache Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Cache
{
    /**
     * Routes
     * 
     * @var array
     */
    private static $routes = [];

    /**
     * Routes patterns
     * 
     * @var array
     */
    private static $patterns = [];

    /**
     * Routes guards
     * 
     * @var array
     */
    private static $guards = [];

    /**
     * Record cache
     * 
     * @var array
     */
    private static $record = [];

    /**
     * Recording state
     * 
     * @var bool
     */
    private static $is_recording = false;

    /**
     * Recorder
     * 
     * @var string
     */
    private static string $recorder = '';

    /**
     * Current route signature
     * 
     * @var \Clicalmani\Routing\Route|null
     */
    private static Route|null $current_route = null;

    /**
     * Current group
     * 
     * @var \Clicalmani\Routing\Group|null
     */
    private static Group|null $current_group = null;

    /**
     * Route hooks
     * 
     * @var array
     */
    private static array $hooks = [];

    /**
     * Returns registered routes signatures for the specified verb.
     * 
     * @param string $verb
     * @return \Clicalmani\Routing\Route[]
     */
    public static function getRoutesByVerb(string $verb) : array
    {
        return @ static::$routes[$verb] ?? [];
    }

    /**
     * Routes getter
     * 
     * @return array
     */
    public static function getRoutes() : array
    {
        return static::$routes;
    }

    /**
     * Routes setter
     * 
     * @return void
     */
    public static function setRoutes(array $routes) : void
    {
        static::$routes = $routes;
    }

    /**
     * Add new route
     * 
     * @param \Clicalmani\Routing\Route
     * @return void
     */
    public static function addRoute(Route $route) : void
    {
        static::$routes[$route->verb][] = $route;
    }

    /**
     * Register a global pattern
     * 
     * @param string $param Parameter name
     * @param string $pattern A regular expression pattern without delimiters
     * @return void
     */
    public static function registerPattern(string $param, string $pattern) : void
    {
        static::$patterns[$param] = $pattern;
    }

    /**
     * Get global patterns
     * 
     * @return array
     */
    public static function getGlobalPatterns() : array
    {
        return static::$patterns;
    }

    /**
     * Register a route guard
     * 
     * @param string $uid Guard's unique id
     * @param string $param Parameter to guard against
     * @param string $callback Callback function
     * @return void
     */
    public static function addGuard(string $uid, string $param, callable $callback) : void
    {
        static::$guards[$uid] = [
            'param' => $param,
            'callback' => $callback
        ];
    }

    /**
     * Returns a registered guard
     * 
     * @param string $uid Guard unique id
     * @return array|null Route guard on success, or null on failure
     */
    public static function getGuard(string $uid) : array|null
    {
        if ( array_key_exists($uid, static::$guards) ) {
            return static::$guards[$uid];
        }
        
        return null;
    }

    /**
     * Gets or sets current route signature.
     * 
     * @param ?string $signature
     * @return mixed
     */
    public static function currentRoute(?Route $route = null) : mixed
    {
        if ($route) return static::$current_route = $route;
        return static::$current_route;
    }

    /**
     * Gets or sets current group.
     * 
     * @param ?Group $group
     * @return mixed
     */
    public static function currentGroup(?Group $group = null) : mixed
    {
        if ($group) return static::$current_group = $group;
        return static::$current_group;
    }

    /**
     * Start recording
     * 
     * @return void
     */
    public static function startRecording(string $name) : void
    {
        static::$recorder = $name;
        static::$is_recording = true;
    }

    /**
     * Stop recording
     * 
     * @return void
     */
    public static function stopRecording() : void
    {
        static::$recorder = '';
        static::$is_recording = false;
    }

    /**
     * Clear record
     * 
     * @return void
     */
    public static function clearRecord() : void
    {
        static::$recorder = '';
        static::$record = [];
    }

    /**
     * Record a route
     * 
     * @param ?\Clicalmani\Routing\Route $route
     * @return mixed
     */
    public static function record(?Route $route = null) : mixed
    {
        if ($route) {
            if (static::$recorder) static::$record[static::$recorder][] = $route;

            foreach (static::$record as $name => $data) {
                if (!$data) continue;
                static::$record[$name][] = $route;
            }

            return null;
        }

        return static::$record;
    }

    /**
     * Returns recording state
     * 
     * @return bool
     */
    public static function isRecording() : bool
    {
        return static::$is_recording;
    }
}
