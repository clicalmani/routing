<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Http\Requests\RequestInterface;
use Clicalmani\Foundation\Providers\ServiceProvider;
use Clicalmani\Foundation\Support\Facades\Config;

/**
 * Route Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Route extends \ArrayObject implements Factory\RouteInterface
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
    private ?int $redirect = null;

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

    public function uri() : string
    {
        $uri = [];

        foreach ($this as $segment) $uri[] = $segment->name;

        return join('/', $uri);
    }

    public function setUri(string $new_uri) : void
    {
        $this->uri = $new_uri;
    }

    public function resetUri() : void
    {
        $this->uri = $this->uri();
    }

    public function removeSegmentAt(int $index, bool $preserve_keys = true) : void
    {
        unset($this[$index]);
        $this->resetUri();

        if (FALSE === $preserve_keys) {
            /** @var \Clicalmani\Routing\Segment[] */
            $segments = [];
            /** @var \Clicalmani\Routing\Segment */
            foreach ($this as $segment) $segments[] = $segment;
            
            $this->exchangeArray($segments);
        }
    }

    public function diff(self $route) : array
    {
        return array_diff($this->getSegmentsNames(), $route->getSegmentsNames());
    }

    public function getSegmentsNames() : array
    {
        $ret = [];
        
        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment) {
            $ret[] = $segment->name;
        }

        return $ret;
    }

    public function equals(Route $route) : bool
    {
        if ($route->uri === $this->uri) return true;
        
        /** @var string[] */
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

    public function seemsOptional() : bool
    {
        return !!preg_match("/\?" . Config::route('parameter_prefix') . ".*([^\/])?/", $this->uri);
    }

    public function getOptions() : array
    {
        $segments = [];

        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment)
            if (preg_match("/^\?" . Config::route('parameter_prefix') . "/", $segment->name)) $segments[] = $segment;

        return $segments;
    }

    public function getSegments() : array
    {
        /** @var \Clicalmani\Routing\Segment[] */
        $segments = [];

        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment) $segments[] = $segment;

        return $segments;
    }

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

    public function getParameters() : array
    {
        /** @var \Clicalmani\Routing\Segment[] */
        $params = [];

        /** @var \Clicalmani\Routing\Segment */
        foreach ($this as $segment) {
            if ($segment->isParameter()) $params[] = $segment;
        }

        return $params;
    }

    public function addMiddleware(mixed $name_or_class) : void
    {
        if ( !in_array($name_or_class, $this->middlewares) ) $this->middlewares[] = $name_or_class;
    }

    public function excludeMiddleware(mixed $name_or_class) : void
    {
        if ( !in_array($name_or_class, $this->excluded_middlewares) ) $this->excluded_middlewares[] = $name_or_class;
    }

    public function getMiddlewares() : array
    {
        if (\Clicalmani\Foundation\Support\Facades\Route::isApi()) {
            $globals = \Clicalmani\Foundation\Http\Middlewares\Api::getGlobals();
        } else {
            $globals = \Clicalmani\Foundation\Http\Middlewares\Web::getGlobals();
        }

        return array_unique(array_diff(array_merge($this->middlewares, $globals), $this->excluded_middlewares));
    }

    public function isAuthorized(?RequestInterface $request = null) : int|bool
    {
        if (!$this->getMiddlewares()) return 200; // Authorized
        
        foreach ($this->getMiddlewares() as $name_or_class) {
            if ($middleware = ServiceProvider::getProvidedMiddleware(\Clicalmani\Foundation\Support\Facades\Route::gateway(), $name_or_class)) ;
            else $middleware = $name_or_class;
            
            if ( $middleware ) {
                /** @var \Clicalmani\Foundation\Http\Response */
                $response = app()->response;
                /** @var \Clicalmani\Psr7\Response|\Clicalmani\Foundation\Http\RedirectInterface */
                $auth = with( new $middleware )->handle(
                    $request,
                    $response,
                    function(mixed $req = null, mixed $resp = null) use($request, $response) {
                        $response = $resp ?? $response;
                        $request = $req ?? $request;
                        return $response->status(http_response_code());
                    }
                );
                
                if ($auth instanceof \Clicalmani\Foundation\Http\Redirect) {
                    die($auth);
                }

                Request::current($request);
                app()->response = $response;
                $response_code = $auth->getStatusCode();

                if (200 !== $response_code) return $response_code;
            }
        }
        
        return 200; // Authorized
    }

    public function isDoubled() : bool
    {
        $count = 0;

        /** @var \Clicalmani\Routing\Route */
        foreach (Memory::getRoutesByVerb($this->verb) as $route) {
            if ($route->name === $this->name) $count++;
        }

        return $count > 1;
    }

    public function beforeHook(?callable $hook = null) : ?callable
    {
        if ($hook) {
            $this->hooks['before'] = $hook;
            return null;
        }

        return @ $this->hooks['before'];
    }

    public function afterHook(?callable $hook = null) : ?callable
    {
        if ($hook) {
            $this->hooks['after'] = $hook;
            return null;
        }

        return @ $this->hooks['after'];
    }

    public function missing(?callable $callback = null) : mixed
    {
        if (NULL === $callback) return @$this->resources['missing'];

        return $this->resources['missing'] = $callback;
    }

    public function orderResultBy(?string $orderBy = null) : mixed
    {
        if (NULL === $orderBy) return @$this->resources['order_by'];

        return $this->resources['order_by'] = $orderBy;
    }

    public function distinctResult(?bool $distinct = null) : mixed
    {
        if (NULL === $distinct) return @$this->resources['distinct'];

        return $this->resources['distinct'] = $distinct;
    }

    public function limitResult(int $offset = 0, int $row_count = 0) : mixed
    {
        if (0 === $row_count) return @$this->resources['limit'];

        $this->resources['limit'] = [
            'offset' => $offset,
            'count' => $row_count
        ];

        return null;
    }

    public function calcFoundRows(?bool $calc = null) : mixed
    {
        if (NULL === $calc) return @$this->resources['calc'];

        return $this->resources['calc'] = $calc;
    }

    public function deleteFrom(?string $table = null) : mixed
    {
        if (NULL === $table) return @$this->resources['from'];

        return $this->resources['from'] = $table;
    }

    public function ignoreKeyWarning(?bool $ignore = null) : mixed
    {
        if (NULL === $ignore) return @$this->resources['ignore'];

        return $this->resources['ignore'] = $ignore;
    }

    public function scoped(array $scope = []) : mixed
    {
        if (empty($scope)) return @$this->resources['scoped'];

        return $this->resources['scoped'] = $scope;
    }

    public function isCustom() : bool
    {
        return preg_match('/^(\{.*\})$/', trim(trim($this->uri), '/'));
    }

    public function named(string $name) : bool
    {
        return !!preg_match("/^$name$/", $this->name);
    }

    public function is(string $uri) : bool
    {
        return !!preg_match("/&$uri$/", $this->uri);
    }

    public function __get(string $name)
    {
        return match ($name) {
            'uri' => $this->uri,
            'action' => $this->action,
            'verb' => $this->verb,
            'name' => $this->name,
            'redirect' => $this->redirect,
        };
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
                    if ( $action = action($value) ) {
                        $this->action = $action;
                    } elseif ($group = Memory::currentGroup()) {
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
                
                if ($group = Memory::currentGroup() AND \Clicalmani\Foundation\Support\Facades\Route::getClientVerb() === $this->verb) {
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
