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

    /**
     * Constructor
     * 
     * @param ?\Closure $callback Call back function
     */
    public function __construct(private ?\Closure $callback = null, ?string $middleware = null) 
    {
        $this->middleware = $middleware;

        Memory::currentGroup($this);
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
        $this->callback = $callback ?: $this->callback;
        return $this->run();
    }

    public function run() : self
    {
        if ($this->callback) call($this->callback);
        Memory::currentGroup(null);
        
        if ($this->hasMiddleware()) {
            foreach ($this->routes as $route) {
                foreach (explode('|', $this->middleware) as $name) $route->addMiddleware($name);
            }
        }

        return $this;
    }

    public function prefix(string $prefix) : self
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

    public function middleware(string|array $name_or_class) : void
    {
        $name_or_class = (array) $name_or_class;

        if ( in_array('web', $name_or_class) ) {
            $name_or_class = array_merge($name_or_class, \Clicalmani\Foundation\Http\Middlewares\Web::getGlobals());
        }

        if ( in_array('api', $name_or_class) ) {
            $name_or_class = array_merge($name_or_class, \Clicalmani\Foundation\Http\Middlewares\Api::getGlobals());
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

    public function where(string $param, string $rule) : self
    {
        /** @var \Clicalmani\Routing\Route */
        foreach ($this->routes as $route) {
            /** @var \Clicalmani\Routing\Segment */
            foreach ($route as $segment) {
                if ($segment->getName() === $param) {
                    $segment->setValidator(new SegmentValidator($param, $rule));
                }
            }
        }
        
        return $this;
    }

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
        return !!$this->middleware;
    }

    public function getMiddleware() : string
    {
        return $this->middleware;
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
