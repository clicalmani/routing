<?php 
namespace Clicalmani\Routing;

/**
 * Group Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Group
{
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
     * @var string
     */
    private string $middleware = '';

    /**
     * Group controller
     * 
     * @var \Clicalmani\Routing\Route[]
     */
    private array $routes = [];

    /**
     * Constructor
     * 
     * @param ?\Closure $callback Call back function
     */
    public function __construct(private ?\Closure $callback = null) 
    {
        Memory::currentGroup($this);
        if (NULL !== $this->callback) $this->group();
    }

    /**
     * Start grouping
     * 
     * @return void
     */
    public function start() : void
    {
        $this->terminate = false;
        call($this->callback);
    }

    /**
     * Stop grouping
     * 
     * @return void
     */
    public function stop() : void
    {
        $this->terminate = true;
    }

    public function isComplete()
    {
        return $this->terminate;
    }

    /**
     * Controller group
     * 
     * @param ?callable $callback
     * @return static
     */
    public function group(?callable $callback = null) : static
    {
        $this->callback = $this->callback ?? $callback;
        $this->run();
        
        return $this;
    }

    /**
     * Run group
     * 
     * @return void
     */
    public function run() : void
    {
        if ($this->callback) call($this->callback);
        Memory::currentGroup(null);

        if ($this->hasMiddleware()) {
            foreach ($this->routes as $route) {
                $route->addMiddleware($this->middleware);
            }
        }
    }

    /**
     * Prefix group's routes
     * 
     * @param string $prefix
     * @return static
     */
    public function prefix(string $prefix) : static
    {
        if ($prefix === \Clicalmani\Foundation\Support\Facades\Config::route('api_prefix')) 
            $this->routes = Memory::getRoutesByVerb(\Clicalmani\Foundation\Routing\Route::getClientVerb());

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

    /**
     * Define one or more middlewares on the routes group
     * 
     * @param string|string[] $name_or_classe Middleware name or class
     * @return void
     */
    public function middleware(string|array $name_or_class) : void
    {
        $name_or_class = (array) $name_or_class;
        
        /** @var \Clicalmani\Routing\Route $route */
        foreach ($this->routes as $route) {
            foreach ($name_or_class as $name) {
                $route->addMiddleware($name);
            }
        }
    }

    /**
     * Set parameter pattern. Useful for optional parameters
     * 
     * @param string $param
     * @param string $pattern
     * @return static
     */
    public function pattern(string $param, string $pattern) : static
    {
        return $this->patterns([$param], [$pattern]);
    }

    /**
     * Set multiple patterns
     * 
     * @see RouteGroup::patterns()
     * @param string[] $params
     * @param string[] $patters
     * @return static
     */
    public function patterns(array $params, array $patterns) : static
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

    /**
     * Validate parameter's value against any validator.
     * 
     * @param string $param
     * @param string $uri
     * @return static
     */
    public function where(string $param, string $uri) : static
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->routes as $route) {
            /** @var \Clicalmani\Routing\Segment */
            foreach ($route as $segment) {
                if ($segment->getName() === $param) {
                    $segment->setValidator(new SegmentValidator($param, $uri));
                }
            }
        }
        
        return $this;
    }

    /**
     * Add group route
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return void
     */
    public function addRoute(Route $route) : void
    {
        $this->routes[] = $route;
    }

    /**
     * Check if group has middleware
     * 
     * @return bool
     */
    public function hasMiddleware() : bool
    {
        return !empty($this->middleware);
    }

    /**
     * Get group middleware
     * 
     * @return string
     */
    public function getMiddleware() : string
    {
        return $this->middleware;
    }

    /**
     * Set group middleware
     * 
     * @param string $middleware
     * @return void
     */
    public function setMiddleware(string $middleware) : void
    {
        $this->middleware = $middleware;
    }

    /**
     * Share resources with a sub-group
     * 
     * @param Group $sub
     * @return void
     */
    public function shareResourcesWith(Group $sub) : void
    {
        if ($this->controller) $sub->controller = $this->controller;
        if ($this->middleware) $sub->middleware = $this->middleware;
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
