<?php
namespace Clicalmani\Routing;

/**
 * Guard Class
 * 
 * Route guards
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Guard 
{
    /**
     * Guard callback
     * 
     * @var \Closure
     */
    private \Closure $callback;

    /**
     * Guard segment
     * 
     * @var \Clicalmani\Routes\Segment
     */
    private Segment $segment;

    public function __construct(private string $uid)
    {
        // ...
    }

    public function __set(string $name, mixed $value)
    {
        switch ($name) {
            case 'segment': $this->segment = $value;
        }
    }

    public function __invoke()
    {
        return call($this->callback, $this->segment->value);
    }
}
