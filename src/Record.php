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
        Memory::startRecording($name);
    }

    /**
     * Stop recording
     * 
     * @return void
     */
    public static function stop() : void
    {
        Memory::stopRecording();
    }

    /**
     * Add recored route
     * 
     * @param \Clicalmani\Routing\Route
     * @return void
     */
    public static function add(Route $route) : void
    {
        Memory::record($route);
    }

    /**
     * Get recorded routes
     * 
     * @return \Clicalmani\Routing\Route[]
     */
    public static function get() : array
    {
        return Memory::record();
    }

    /**
     * Clear memory record
     * 
     * @return void
     */
    public static function clear() : void
    {
        Memory::clearRecord();
    }
}
