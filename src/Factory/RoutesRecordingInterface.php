<?php
namespace Clicalmani\Routing\Factory;

interface RoutesRecordingInterface
{
    /**
     * Start recording for the given name
     * 
     * @param string $name Middleware name
     * @return void
     */
    public static function start(string $name) : void;

    /**
     * Stop recording
     * 
     * @return void
     */
    public static function stop() : void;

    /**
     * Add recored route
     * 
     * @param \Clicalmani\Routing\Route
     * @return void
     */
    public static function add(\Clicalmani\Routing\Route $route) : void;

    /**
     * Get recorded routes
     * 
     * @return \Clicalmani\Routing\Route[]
     */
    public static function get() : array;

    /**
     * Clear memory record
     * 
     * @return void
     */
    public static function clear() : void;
}