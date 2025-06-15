<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Collection\Collection;

/**
 * Resource Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Resource extends \ArrayObject
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

    /**
     * Override the default not found behaviour.
     * 
     * @param callable $closure A closure function that returns the response type.
     * @return self
     */
    public function missing(callable $closure) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && in_array(@$route->action[1], ['create', 'show', 'edit']) ) $route->missing($closure);
        }

        return $this;
    }

    /**
     * Show distinct rows on resource view
     * 
     * @param bool $enable
     * @return self
     */
    public function distinct(bool $enable = false) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->distinctResult($enable);
        }

        return $this;
    }

    /**
     * Ignore primary key duplicate warning
     * 
     * @param bool $enable
     * @return self
     */
    public function ignore(bool $enable = false) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && (@$route->action[1] === 'update' || @$route->action[1] === 'store')) 
                $route->ignoreKeyWarning($enable);
        }

        return $this;
    }

    /**
     * From statement when deleting from multiple tables
     * 
     * @param string $table
     * @return self
     */
    public function from(string $table) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'destroy') $route->deleteFrom($table);
        }

        return $this;
    }

    /**
     * Enable SQL CAL_FOUND_ROWS
     * 
     * @param bool $enable
     * @return self
     */
    public function calcRows(bool $enable = false) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->calcFoundRows($enable);
        }

        return $this;
    }

    /**
     * Limit number of rows in the result set
     * 
     * @param int $offset
     * @param int $row_count
     * @return self
     */
    public function limit(int $offset = 0, int $row_count = 0) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->limitResult($offset, $row_count);
        }

        return $this;
    }

    /**
     * Order by
     * 
     * @param string $order
     * @return self
     */
    public function orderBy(string $order = 'NULL') : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->orderResultBy($order);
        }

        return $this;
    }

    /**
     * @return self
     */
    public function middleware(string $name_or_class) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            $route?->addMiddleware($name_or_class);
        }

        return $this;
    }

    /**
     * Filter the resource to only include specified actions.
     * 
     * @param string|array $action
     * @return self
     */
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

    /**
     * Filter the resource to exclude specified actions.
     * 
     * @param string|array $action
     * @return self
     */
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

    /**
     * Scope the resource routes.
     * 
     * @param array $scope
     * @return self
     */
    public function scoped(array $scope) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            $route->scoped($scope);
        }

        return $this;
    }

    /**
     * Defines shallow nested routes.
     * 
     * @return self
     */
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

    /**
     * Set custom names for the resource routes.
     * 
     * @param array $custom_names
     * @return self
     */
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
