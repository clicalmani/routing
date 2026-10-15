<?php 
namespace Clicalmani\Routing;

/**
 * Group Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Group implements Factory\GroupInterface
{
    use ValidationRules;
    
    /**
     * Terminate grouping
     * 
     * @var bool
     */
    private bool $terminate = false;

    /**
     * Group controller
     * 
     * @var string
     */
    private string $controller = '';

    /**
     * Group middleware
     * 
     * @var ?string
     */
    private ?string $middleware = null;

    /**
     * Group controller
     * 
     * @var \Clicalmani\Routing\Route[]
     */
    private array $routes = [];

    private ?Group $parent = null;
    private bool $ran = false;

    /**
     * Constructor
     * 
     * @param ?\Closure $callback Call back function
     */
    public function __construct(private ?\Closure $callback = null, ?string $middleware = null) 
    {
        $this->middleware = $middleware;

        $this->parent = Registry::currentGroup();
        Registry::currentGroup($this);
        if (NULL !== $this->callback) $this->group();
    }

    public function start() : void
    {
        $this->terminate = false;
        call($this->callback);
    }

    public function stop() : void
    {
        $this->terminate = true;
    }

    public function isComplete() : bool
    {
        return $this->terminate;
    }

    public function group(?callable $callback = null) : self
    {
        if ($callback) {
            $this->callback = $callback;
            $this->ran = false;             
        }

        return $this->run();
    }

    public function run() : self
    {
        if ($this->callback && !$this->ran) {
            $this->ran = true;
            call($this->callback);
        }

        Registry::setCurrentGroup($this->parent);

        if ($this->hasMiddleware()) {
            foreach ($this->routes as $route) {
                foreach (explode('|', $this->middleware) as $name) $route->addMiddleware($name);
            }
        }

        return $this;
    }

    public function prefix(string $prefix) : self
    {
        if ($prefix === \Clicalmani\Core\Support\Facades\Config::route('api_prefix')) {
            $this->routes = Registry::getRoutesByVerb(\Clicalmani\Core\Support\Facades\Route::getClientVerb());
        }

        foreach ($this->routes as $route) {
            $new_segment = new Segment;
            $new_segment->name = $prefix;
            $segments = $route->getSegments();
            array_unshift($segments, $new_segment);
            
            foreach ($segments as $index => $segment) {
                $route[$index] = $segment;
            }
        }

        return $this;
    }

    public function middleware(string|array $name_or_class) : void
    {
        $name_or_class = (array) $name_or_class;

        if ( in_array('web', $name_or_class) ) {
            $name_or_class = array_merge($name_or_class, \Clicalmani\Core\Http\Middlewares\Web::getGlobals());
        }

        if ( in_array('api', $name_or_class) ) {
            $name_or_class = array_merge($name_or_class, \Clicalmani\Core\Http\Middlewares\Api::getGlobals());
        }
        
        /** @var \Clicalmani\Routing\Route $route */
        foreach ($this->routes as $route) {
            foreach ($name_or_class as $name) {
                $route->addMiddleware($name);
            }
        }
    }

    public function withoutMiddleware(string|array $name_or_class) : void
    {
        $name_or_class = (array) $name_or_class;
        
        /** @var \Clicalmani\Routing\Route $route */
        foreach ($this->routes as $route) {
            foreach ($name_or_class as $name) {
                $route->excludeMiddleware($name);
            }
        }
    }

    public function pattern(string $param, string $pattern) : self
    {
        return $this->patterns([$param], [$pattern]);
    }

    public function patterns(array $params, array $patterns) : self
    {
        foreach ($this->routes as $route) {
            foreach ($params as $index => $param) {
                /** @var \Clicalmani\Routing\Segment */
                foreach ($route as $segment) {
                    if ($segment->getName() === $param) {
                        $segment->setValidator(new SegmentValidator($param, 'regexp|pattern:' . $patterns[$index]));
                    }
                }
            }
        }

        return $this;
    }

    public function where(string|array $params, string $rule) : self
    {
        $params = (array) $params;

        foreach ($this->routes as $route) {
            foreach ($route as $segment) {
                if ($segment->isParameter() && in_array($segment->getName(), $params, true)) {
                    $segment->setValidator(new SegmentValidator($segment->getName(), $rule));
                }
            }
        }

        return $this;
    }

    public function addRoute(Route $route) : void
    {
        if (!in_array($route, $this->routes, true)) $this->routes[] = $route;
        $this->parent?->addRoute($route);
    }

    /**
     * Check if group has middleware
     * 
     * @return bool
     */
    public function hasMiddleware() : bool
    {
        return !!$this->middleware;
    }

    public function getMiddleware() : string
    {
        return (string) $this->middleware;
    }

    public function setMiddleware(string $middleware) : void
    {
        $this->middleware = $middleware;
    }

    public function shareResourcesWith(Group $sub) : void
    {
        if ($this->controller) $sub->controller = $this->controller;
        if ($this->middleware) $sub->middleware = $this->middleware;
    }

    public function name(string $name)
    {
        foreach ($this->routes as $route) {
            foreach (Registry::getRoutesByVerb($route->verb) as $other) {
                if ($other->name === $name && !in_array($other, $this->routes, true)) {
                    throw new \Exception(sprintf("There is an existing route with the same name %s.", $name));
                }
            }
        }

        foreach ($this->routes as $route) $route->name = $name;
    }

    /**
     * (non-PHPDoc)
     * @overriden
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'controller') return $this->controller;
        if ($name === 'routes') return $this->routes;
        if ($name === 'middleware') return $this->middleware;
    }

    /**
     * (non-PHPDoc)
     * @overriden
     * 
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function __set(string $name, mixed $value)
    {
        if ($name === 'controller') $this->controller = $value;
        if ($name === 'routes') $this->routes = $value;
        if ($name === 'middleware') $this->middleware = $value;
    }
}
