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
     * Guard path
     * 
     * @var \Clicalmani\Routes\Path
     */
    private Path $path;

    public function __construct(private string $uid)
    {
        // ...
    }

    public function __set(string $name, mixed $value)
    {
        switch ($name) {
            case 'path': $this->path = $value;
        }
    }

    public function __invoke()
    {
        return call($this->callback, $this->path->value);
    }
}
