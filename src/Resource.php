<?php
namespace Clicalmani\Routing;

use Clicalmani\Fundation\Collection\Collection;

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
        $this->storage->add($value);
    }

    /**
     * Override the default not found behaviour.
     * 
     * @param callable $closure A closure function that returns the response type.
     * @return static
     */
    public function missing(callable $closure) : static
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'create') $route->missing($closure);
        }

        return $this;
    }

    /**
     * Show distinct rows on resource view
     * 
     * @param bool $enable
     * @return static
     */
    public function distinct(?bool $enable = false) : static
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
     * @return static
     */
    public function ignore(?bool $enable = false) : static
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
     * @return static
     */
    public function from(string $table) : static
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
     * @return static
     */
    public function calcRows(?bool $enable = false) : static
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
     * @param ?int $offset
     * @param ?int $row_count
     * @return static
     */
    public function limit(?int $offset = 0, ?int $row_count = 0) : static
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
     * @return static
     */
    public function orderBy(?string $order = 'NULL') : static
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            if ( is_array($route->action) && @$route->action[1] === 'index') $route->orderResultBy($order);
        }

        return $this;
    }

    public function middleware(string $name_or_class)
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->storage as $route) {
            $route?->addMiddleware($name_or_class);
        }

        return $this;
    }
}
