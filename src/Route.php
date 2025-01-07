<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Http\Requests\Request;
use Clicalmani\Foundation\Http\Response\Response;
use Clicalmani\Foundation\Providers\ServiceProvider;
use Clicalmani\Foundation\Support\Facades\Config;

/**
 * Route Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Route extends \ArrayObject
{
    /**
     * Route uri
     * 
     * @var string
     */
    private string $uri = '';

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
     * Excluded middlewares
     * 
     * @var array
     */
    private array $excluded_middlewares = [];

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

        $this->uri = $this->uri();
    }

    public function uri()
    {
        $uri = [];

        foreach ($this as $segment) $uri[] = $segment->name;

        return join('/', $uri);
    }

    /**
     * URI setter
     * 
     * @param string $new_uri
     * @return void
     */
    public function setUri(string $new_uri) : void
    {
        $this->uri = $new_uri;
    }

    /**
     * Find the difference of two routes.
     * 
     * @param static $route
     * @return string[]
     */
    public function diff(Route $route) : array
    {
        return array_diff($this->getSegmentsNames(), $route->getSegmentsNames());
    }

    /**
     * Returns an array of route segments' names.
     * 
     * @return string[]
     */
    public function getSegmentsNames() : array
    {
        $ret = [];
        
        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment) {
            $ret[] = $segment->name;
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
        if ($route->uri === $this->uri) return true;
        
        $ret = [];

        /** @var \Clicalmani\Routing\Segment */
        foreach ($route as $segment) {
            if ($segment->isParameter()) {
                if ($segment->isValid()) $ret[] = $segment->value;
                else $ret[] = $segment->name;
            } else $ret[] = $segment->name;
        }
        
        if (join('', $ret) === join('', $this->getSegmentsNames())) return true;

        return false;
    }

    /**
     * Check if there is one or more optional parameters.
     * 
     * @return bool
     */
    public function seemsOptional() : bool
    {
        return !!preg_match("/\?" . Config::route('parameter_prefix') . ".*([^\/])?/", $this->uri);
    }

    /**
     * Returns optional segments
     * 
     * @return \Clicalmani\Routing\Segment[]
     */
    public function getOptions() : array
    {
        $segments = [];

        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment)
            if (preg_match("/^\?" . Config::route('parameter_prefix') . "/", $segment->name)) $segments[] = $segment;

        return $segments;
    }

    /**
     * Returns route segments
     * 
     * @return \Clicalmani\Routing\Segment[]
     */
    public function getSegments() : array
    {
        $segments = [];

        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment) $segments[] = $segment;

        return $segments;
    }

    /**
     * Remove all optional segments
     * 
     * @return void
     */
    public function makeRequired() : void
    {
        $options = [];

        /** 
         * @var int $index 
         * @var \Clicalmani\Routing\Segment 
         */
        foreach ($this as $index => $segment) 
            if ($segment->isOptional()) $options[] = $index;

        foreach ($options as $index) unset($this[$index]);

        $this->uri = $this->uri();
    }

    /**
     * Get route parameters
     * 
     * @return \Clicalmani\Routing\Segment[]
     */
    public function getParameters()
    {
        /** @var \Clicalmani\Routing\Segment[] */
        $params = [];

        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment) {
            if ($segment->isParameter()) $params[] = $segment;
        }

        return $params;
    }

    /**
     * Add a new middleware
     * 
     * @param mixed $name_or_class
     * @return void
     */
    public function addMiddleware(mixed $name_or_class) : void
    {
        if ( !in_array($name_or_class, $this->middlewares) ) $this->middlewares[] = $name_or_class;
    }

    /**
     * Remove a middleware
     * 
     * @param mixed $name_or_class
     * @return void
     */
    public function excludeMiddleware(mixed $name_or_class) : void
    {
        if ( !in_array($name_or_class, $this->excluded_middlewares) ) $this->excluded_middlewares[] = $name_or_class;
    }

    /**
     * Get route middlewares
     * 
     * @return array
     */
    public function getMiddlewares() : array
    {
        return array_diff($this->middlewares, $this->excluded_middlewares);
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
            if ($middleware = ServiceProvider::getProvidedMiddleware(\Clicalmani\Foundation\Routing\Route::gateway(), $name_or_class)) ;
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
        foreach (Memory::getRoutesByVerb($this->verb) as $route) {
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

    /**
     * Check custom route
     * 
     * @return bool
     */
    public function isCustom() : bool
    {
        return preg_match('/^(\{.*\})$/', trim(trim($this->uri), '/'));
    }

    /**
     * Check if route is named
     * 
     * @param string $name
     * @return bool
     */
    public function named(string $name) : bool
    {
        return $this->name === $name;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'uri': return $this->uri;
            case 'action': return $this->action;
            case 'verb': return $this->verb;
            case 'name': return $this->name;
            case 'redirect': return $this->redirect;
        }
    }

    public function __set(string $name, mixed $value)
    {
        switch ($name) {
            case 'uri': $this->uri = $value; break;
            case 'verb': $this->verb = $value; break;
            case 'name': $this->name = $value; break;
            case 'redirect': $this->redirect = $value; break;
            case 'action': 

                /**
                 * Method action
                 */
                if ( is_array($value) AND count($value) === 2 ) {
                    $this->action = $value;
                } 
                
                /**
                 * Controller method action
                 */
                elseif ( is_string($value) && $value ) {
                    
                    if ($group = Memory::currentGroup()) {
                        if ($group->controller) $this->action = [$group->controller, $value];
                        else $this->action = [$value, '__invoke'];
                    }
                } 
    
                /**
                 * Anonymous action
                 */
                elseif ( is_callable($value) ) $this->action = $value;
                
                /**
                 * Controller class action
                 */
                elseif (!$value) {
                    $this->action = '__invoke';
                }
                
                if ($group = Memory::currentGroup() AND \Clicalmani\Foundation\Routing\Route::getClientVerb() === $this->verb) {
                    $group->addRoute($this);
                }
            break;
        }
    }

    public function __clone()
    {
        $route = new self;

        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment) $route[] = $segment;

        return $route;
    }

    public function __toString()
    {
        return $this->uri();
    }
}
