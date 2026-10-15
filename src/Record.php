<?php
namespace Clicalmani\Routing;

/**
 * Record Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Record implements Factory\RoutesRecordingInterface
{
    public static function start(string $name) : void
    {
        Registry::startRecording($name);
    }

    public static function stop() : void
    {
        Registry::stopRecording();
    }

    public static function add(Route $route) : void
    {
        Registry::record($route);
    }

    public static function get() : array
    {
        return Registry::record();
    }

    public static function clear() : void
    {
        Registry::clearRecord();
    }
}
