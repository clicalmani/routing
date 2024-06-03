<?php
namespace Clicalmani\Routing;

/**
 * Record Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Record
{
    /**
     * Start recording for the given name
     * 
     * @param string $name Middleware name
     * @return void
     */
    public static function start(string $name) : void
    {
        Cache::startRecording($name);
    }

    /**
     * Stop recording
     * 
     * @return void
     */
    public static function stop() : void
    {
        Cache::stopRecording();
    }

    /**
     * Add recored route
     * 
     * @param \Clicalmani\Routing\Route
     * @return void
     */
    public static function add(Route $route) : void
    {
        Cache::record($route);
    }

    /**
     * Get recorded routes
     * 
     * @return \Clicalmani\Routing\Route[]
     */
    public static function get() : array
    {
        return Cache::record();
    }

    /**
     * Clear cache
     * 
     * @return void
     */
    public static function clear() : void
    {
        Cache::clearRecord();
    }
}
