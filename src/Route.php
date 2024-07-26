<?php
namespace Clicalmani\Routing;

use Clicalmani\Fundation\Http\Requests\Request;
use Clicalmani\Fundation\Http\Response\Response;
use Clicalmani\Fundation\Providers\ServiceProvider;

/**
 * Route Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Route extends \ArrayObject
{
    /**
     * Route signature
     * 
     * @var string
     */
    private string $signature = '';

    /**
     * Route verb
     * 
     * @var string
     */
    private string $verb = '';

    /**
     * Route controller action
     * 
     * @var mixed
     */
    private mixed $action = null;

    /**
     * Route name
     * Useful for named route.
     * 
     * @var string
     */
    private string $name = '';

    /**
     * Route middlewares
     * 
     * @var array
     */
    private array $middlewares = [];

    /**
     * Route resources
     * 
     * @var array
     */
    private array $resources = [];

    /**
     * Route hooks
     * 
     * @var array
     */
    private array $hooks = [
                                'before' => null, // Before navigation hook
                                'after' => null   // After navigation hook
                            ];

    /**
     * Redirect
     * 
     * @var int
     */
    private int|null $redirect = null;

    /**
     * @override
     * @param mixed $index 
     * @param mixed $newval
     * @return void
     */
    public function offsetSet(mixed $index, mixed $newval) : void
    {
        parent::offsetSet($index, $newval);

        $this->signature = $this->getSignature();
    }

    public function getSignature()
    {
        $signature = [];

        foreach ($this as $path) $signature[] = $path->name;

        return join('/', $signature);
    }

    /**
     * Signature setter
     * 
     * @param string $new_signature
     * @return void
     */
    public function setSignature(string $new_signature) : void
    {
        $this->signature = $new_signature;
    }

    /**
     * Find the difference of two routes.
     * 
     * @param static $route
     * @return string[]
     */
    public function diff(Route $route) : array
    {
        return array_diff($this->getPathNameArray(), $route->getPathNameArray());
    }

    /**
     * Returns an array of route paths names.
     * 
     * @return string[]
     */
    public function getPathNameArray() : array
    {
        $ret = [];
        
        /** @var \Clicalmani\Routing\Path */
        foreach ($this as $path) {
            $ret[] = $path->name;
        }

        return $ret;
    }

    /**
     * Compare the given route to the current route.
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return bool
     */
    public function equals(Route $route) : bool
    {
        if ($route->signature === $this->signature) return true;
        
        $ret = [];

        /** @var \Clicalmani\Routing\Path */
        foreach ($route as $path) {
            if ($path->isParameter()) {
                if ($path->isValid()) $ret[] = $path->value;
                else $ret[] = $path->name;
            } else $ret[] = $path->name;
        }
        
        if (join('', $ret) === join('', $this->getPathNameArray())) return true;

        return false;
    }

    /**
     * Check if there is one or more optional parameters.
     * 
     * @return bool
     */
    public function seemsOptional()
    {
        return preg_match('/\?:.*([^\/])?/', $this->signature);
    }

    /**
     * Returns optional paths
     * 
     * @return \Clicalmani\Routing\Path[]
     */
    public function getOptions() : array
    {
        $paths = [];

        /** @var \Clicalmani\Routing\Path */
        foreach ($this as $path)
            if (preg_match('/^\?:/', $path->name)) $paths[] = $path;

        return $paths;
    }

    /**
     * Returns route paths
     * 
     * @return \Clicalmani\Routing\Path[]
     */
    public function getPaths() : array
    {
        $paths = [];

        /** @var \Clicalmani\Routing\Path */
        foreach ($this as $path) $paths[] = $path;

        return $paths;
    }

    /**
     * Remove all optional paths
     * 
     * @return void
     */
    public function makeRequired() : void
    {
        $options = [];

        /** 
         * @var int $index 
         * @var \Clicalmani\Routing\Path 
         */
        foreach ($this as $index => $path) 
            if ($path->isOptional()) $options[] = $index;

        foreach ($options as $index) unset($this[$index]);

        $this->signature = $this->getSignature();
    }

    /**
     * Get route parameters
     * 
     * @return \Clicalmani\Routing\Path[]
     */
    public function getParameters()
    {
        /** @var \Clicalmani\Routing\Path[] */
        $params = [];

        /** @var \Clicalmani\Routing\Path */
        foreach ($this as $path) {
            if ($path->isParameter()) $params[] = $path;
        }

        return $params;
    }

    /**
     * Add a new middleware
     * 
     * @param mixed $middleware
     */
    public function addMiddleware(mixed $name_or_class)
    {
        if ( !in_array($name_or_class, $this->middlewares) ) $this->middlewares[] = $name_or_class;
    }

    /**
     * Verfify if route is authorized
     * 
     * @return int|bool
     */
    public function isAuthorized(?Request $request = null) : int|bool
    {
        if (!$this->middlewares) return 200; // Authorized
        
        foreach ($this->middlewares as $name_or_class) {
            if ($middleware = ServiceProvider::getProvidedMiddleware(\Clicalmani\Fundation\Routing\Route::gateway(), $name_or_class)) ;
            else $middleware = $name_or_class;
            
            if ( $middleware )
                with( new $middleware )->handle(
                    $request,
                    new Response,
                    fn() => http_response_code()
                );

            $response_code = http_response_code();
            
            if (200 !== $response_code) return $response_code;
        }
        
        return 200; // Authorized
    }

    /**
     * Verify for an existing named route with the same name.
     * 
     * @return bool
     */
    public function isDoubled()
    {
        $count = 0;

        /** @var \Clicalmani\Routing\Route */
        foreach (Cache::getRoutesByVerb($this->verb) as $route) {
            if ($route->name === $this->name) $count++;
        }

        return $count > 1;
    }

    /**
     * Before navigation hook
     * 
     * @return ?callable|null
     */
    public function beforeHook(?callable $hook = null) : callable|null
    {
        if ($hook) {
            $this->hooks['before'] = $hook;
            return null;
        }

        return @ $this->hooks['before'];
    }

    /**
     * After navigation hook
     * 
     * @return ?callable|null
     */
    public function afterHook(?callable $hook = null) : callable|null
    {
        if ($hook) {
            $this->hooks['after'] = $hook;
            return null;
        }

        return @ $this->hooks['after'];
    }

    /**
     * Missing callback
     * 
     * @param ?callable $callback
     * @return mixed
     */
    public function missing(?callable $callback = null) : mixed
    {
        if (NULL === $callback) return @$this->resources['missing'];

        $this->resources['missing'] = $callback;
        
        return null;
    }

    /**
     * Order route result
     * 
     * @param ?string $orderBy
     * @return mixed
     */
    public function orderResultBy(?string $orderBy = null) : mixed
    {
        if (NULL === $orderBy) return @$this->resources['order_by'];

        $this->resources['order_by'] = $orderBy;

        return null;
    }

    /**
     * Distinct result
     * 
     * @param ?bool $distinct
     * @return mixed
     */
    public function distinctResult(?bool $distinct = null) : mixed
    {
        if (NULL === $distinct) return @$this->resources['distinct'];

        $this->resources['distinct'] = $distinct;

        return null;
    }

    /**
     * Limit result set
     * 
     * @param ?int $offset
     * @param ?int $row_count
     * @return mixed
     */
    public function limitResult(?int $offset = 0, ?int $row_count = 0) : mixed
    {
        if (0 === $row_count) return @$this->resources['limit'];

        $this->resources['limit'] = [
            'offset' => $offset,
            'count' => $row_count
        ];

        return null;
    }

    /**
     * Enable SQL CALC_FOUND_ROWS on the request query.
     * 
     * @param ?bool $calc
     * @return mixed
     */
    public function calcFoundRows(?bool $calc = null) : mixed
    {
        if (NULL === $calc) return @$this->resources['calc'];

        $this->resources['calc'] = $calc;

        return null;
    }

    /**
     * Specify the table to delete from when deleting from
     * multiple tables.
     * 
     * @param ?string $table
     * @return mixed
     */
    public function deleteFrom(?string $table = null) : mixed
    {
        if (NULL === $table) return @$this->resources['from'];

        $this->resources['from'] = $table;

        return null;
    }

    /**
     * Ignore primary key duplic warning
     * 
     * @param ?bool $ignore
     * @return mixed
     */
    public function ignoreKeyWarning(?bool $ignore = null) : mixed
    {
        if (NULL === $ignore) return @$this->resources['ignore'];

        $this->resources['ignore'] = $ignore;

        return null;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'signature': return $this->signature;
            case 'action': return $this->action;
            case 'verb': return $this->verb;
            case 'name': return $this->name;
            case 'redirect': return $this->redirect;
        }
    }

    public function __set(string $name, mixed $value)
    {
        switch ($name) {
            case 'signature': $this->signature = $value; break;
            case 'action': $this->action = $value; break;
            case 'verb': $this->verb = $value; break;
            case 'name': $this->name = $value; break;
            case 'redirect': $this->redirect = $value; break;
        }
    }

    public function __clone()
    {
        $route = new self;

        /** @var \Clicalmani\Routing\Path */
        foreach ($this as $path) $route[] = $path;

        return $route;
    }

    public function __toString()
    {
        return $this->getSignature();
    }
}
