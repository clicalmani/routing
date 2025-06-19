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
        Memory::startRecording($name);
    }

    public static function stop() : void
    {
        Memory::stopRecording();
    }

    public static function add(Route $route) : void
    {
        Memory::record($route);
    }

    public static function get() : array
    {
        return Memory::record();
    }

    public static function clear() : void
    {
        Memory::clearRecord();
    }
}
