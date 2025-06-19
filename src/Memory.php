<?php
namespace Clicalmani\Routing;

/**
 * Memory Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Memory implements Factory\RouteMemoryInterface
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
     * Record in memory
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
     * Current route
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

    public static function getRoutesByVerb(string $verb) : array
    {
        return @ static::$routes[$verb] ?? [];
    }

    public static function getRoutes() : array
    {
        return static::$routes;
    }

    public static function setRoutes(array $routes) : void
    {
        static::$routes = $routes;
    }

    public static function addRoute(Route $route) : void
    {
        static::$routes[$route->verb][] = $route;
    }

    public static function removeRoute(Route $route, string $verb) : bool
    {
        /** @var \Clicalmani\Routing\Route $r */
        foreach (static::$routes[$verb] as $i => $r) {
            if ($route->equals($r)) {
                unset(static::$routes[$verb][$i]);
                return true;
            }
        }

        return false;
    }

    public static function registerPattern(string $param, string $pattern) : void
    {
        static::$patterns[$param] = $pattern;
    }

    public static function registerConstraint(string $param, string $constraint) : void
    {
        static::$patterns[$param] = $constraint;
    }

    public static function getGlobalPatterns() : array
    {
        return static::$patterns;
    }

    public static function addGuard(string $uid, string $param, callable $callback) : void
    {
        static::$guards[$uid] = [
            'param' => $param,
            'callback' => $callback
        ];
    }

    public static function getGuard(string $uid) : ?array
    {
        if ( array_key_exists($uid, static::$guards) ) {
            return static::$guards[$uid];
        }
        
        return null;
    }

    public static function currentRoute(?Route $route = null) : mixed
    {
        if ($route) return static::$current_route = $route;
        return static::$current_route;
    }

    public static function currentGroup(?Group $group = null) : mixed
    {
        if ($group) return static::$current_group = $group;
        return static::$current_group;
    }

    public static function startRecording(string $name) : void
    {
        static::$recorder = $name;
        static::$is_recording = true;
    }

    public static function stopRecording() : void
    {
        static::$recorder = '';
        static::$is_recording = false;
    }

    public static function clearRecord() : void
    {
        static::$recorder = '';
        static::$record = [];
    }

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

    public static function isRecording() : bool
    {
        return static::$is_recording;
    }
}
