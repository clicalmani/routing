<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Collection\Collection;

/**
 * Resource Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Resource extends \ArrayObject implements Factory\RouteResourceInterface
{
    public function __construct(private ?Collection $storage = new Collection) {}

    /**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet(mixed $index, mixed $value) : void
	{
        if (get_class($value) === \Clicalmani\Routing\Validator::class) {
            $this->storage->add($value->route);
        } elseif (get_class($value) === \Clicalmani\Routing\Group::class) {
            /** @var \Clicalmani\Routing\Route */
            foreach ($value->routes as $route) {
                $this->storage->add($route);
            }
        }
    }

    public function missing(callable $closure) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && in_array(@$route->action[1], ['create', 'show', 'edit']) ) $route->missing($closure);
        }

        return $this;
    }

    public function distinct(bool $enable = false) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->distinctResult($enable);
        }

        return $this;
    }

    public function ignore(bool $enable = false) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && (@$route->action[1] === 'update' || @$route->action[1] === 'store')) 
                $route->ignoreKeyWarning($enable);
        }

        return $this;
    }

    public function from(string $table) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'destroy') $route->deleteFrom($table);
        }

        return $this;
    }

    public function calcRows(bool $enable = false) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->calcFoundRows($enable);
        }

        return $this;
    }

    public function limit(int $offset = 0, int $row_count = 0) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->limitResult($offset, $row_count);
        }

        return $this;
    }

    public function orderBy(string $order = 'NULL') : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->orderResultBy($order);
        }

        return $this;
    }

    public function middleware(string $name_or_class) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            $route?->addMiddleware($name_or_class);
        }

        return $this;
    }

    public function only(string|array $action) : self
    {
        $action = (array) $action;

        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if (is_array($route->action) && !in_array(@$route->action[1], $action)) 
                Memory::removeRoute($route, $route->verb);
        }

        return $this;
    }

    public function except(string|array $action) : self
    {
        $action = (array) $action;
        
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if (is_array($route->action) && in_array(@$route->action[1], $action)) {
                Memory::removeRoute($route, $route->verb);
            }
        }

        return $this;
    }

    public function scoped(array $scope) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            $route->scoped($scope);
        }

        return $this;
    }

    public function shallow() : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            /** @var \Clicalmani\Routing\Segment $segment */
            foreach ($route as $index => $segment) {
                if (!in_array(@$route->action[1], ['index', 'create', 'store']) &&
                        $segment->name === \Clicalmani\Foundation\Support\Facades\Config::route('parameter_prefix').'id') {
                    $route->removeSegmentAt($index, false);
                    $route->removeSegmentAt($index - 1, false);
                } 
            }
        }

        return $this;
    }

    public function names(array $custom_names) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if (isset($custom_names[$route->action[1]])) {
                $route->name = $custom_names[$route->action[1]];
            }
        }

        return $this;
    }
}
