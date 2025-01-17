<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Providers\ServiceProvider;
use Clicalmani\Foundation\Support\Facades\Config;

/**
 * Routing Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Routing
{
    use Method;

    /**
     * Returns client's verb
     * 
     * @return string
     */
    public function getClientVerb() : string
    {
        return strtolower( (string) @ $_SERVER['REQUEST_METHOD'] );
    }

    /**
     * Get client's gateway
     * 
     * @return string
     */
    public function gateway() : string
    {
        return $this->isApi() ? 'api': 'web';
    }

    /**
     * Detect api gateway
     * 
     * @return bool
     */
    public function isApi() : bool
    {
        if ( inConsoleMode() && defined('CONSOLE_API_ROUTE') ) return true;
        
        $api = \Clicalmani\Foundation\Support\Facades\Config::route('api_prefix');
        
        return preg_match(
            "/^\/$api/", 
            client_uri()
        );
    }

    /**
     * Group routes under a common prefix or middleware
     */
    public function group(mixed ...$parameters) : \Clicalmani\Routing\Group|null
    {
        switch( count($parameters) ) {
            case 1: return new Group($parameters[0]);
            case 2: 
                /** @var array */
                $args = $parameters[0];
                /** @var callable */
                $callback = $parameters[1];
                break;
        }

        // Prefix routes
        if ( isset($args['prefix']) AND $prefix = $args['prefix']) {
            $group = with( new Group($callback) )->prefix($prefix);
            if ( isset($args['where']) ) $group->where(array_keys($args['where'])[0], array_values($args)[0]);
            return $group;
        }
        
        // Middleware
        if ( isset($args['middleware']) AND $name = $args['middleware']) 
            foreach (explode('|', $name) as $name) $this->middleware($name);
        
        return new Group($callback ?? null);
    }

    /**
     * Attach a middleware
     * 
     * @param string $name_or_class
     * @param mixed $callback If omitted the middleware will be considered as an inline middleware
     * @return \Clicalmani\Routing\Group
     */
    public function middleware(string $name_or_class, mixed $callback = null) : Group
    {
        if ( $middleware = $this->getMiddleware($name_or_class) ) {
            $this->registerMiddleware($callback ? $callback: $middleware, $name_or_class);
            $group = new Group;
            $group->setMiddleware($name_or_class);
            return $group;
        } else
            throw new Exceptions\MiddlewareNotFoundException(
                sprintf("Unknow middleware %s specified", $name_or_class)
            );
    }

    /**
     * Get a middleware by name or class
     * 
     * @param string $name_or_class
     * @return mixed
     */
    private function getMiddleware(string $name_or_class) : mixed
    {
        /** @var string */
        $main = $this->parseMiddleware($name_or_class)['main'];

        /**
         * Inline middleware
         */
        if (class_exists($main)) $middleware = $main;

        /**
         * Global middleware
         */
        else $middleware = ServiceProvider::getProvidedMiddleware($this->gateway(), $main);

        if ( NULL === $middleware ) return null;
        
        return new $middleware;
    }

    /**
     * Register a middleware
     * 
     * @param mixed $middleware
     * @param string $name_or_class
     * @return void
     */
    private function registerMiddleware(mixed $middleware, string $name_or_class) : void
    {
        $ret = $this->parseMiddleware($name_or_class);
        /** @var string */
        $main = $ret['main'];
        /** @var string[] */
        $subs = $ret['subs'];

        Record::start($main);
        
        if (method_exists($middleware, 'boot')) $middleware->boot();
        elseif (is_callable($middleware)) $middleware();
        
        $routes = Record::get();
        
        if ( array_key_exists($main, $routes) ) {
            /** @var \Clicalmani\Routing\Route $route */
            foreach ($routes[$main] as $route) {
                $route->addMiddleware($main);
                /** @var string $sub */
                foreach ($subs as $sub) $route->addMiddleware($sub);
            }
        }
        
        Record::stop();
    }

    /**
     * Parse middleware
     * 
     * @param string $name
     * @return object
     */
    private function parseMiddleware(string $name)
    {
        $arr = preg_split('/[,]/', strtr($name, '@[]', ',,,'), -1, PREG_SPLIT_NO_EMPTY);
        $main = array_shift($arr);

        return [
            'main' => $main,
            'subs' => collection($arr)->map(fn(string $name) => trim($name))->toArray()
        ];
    }

    /**
     * Set a global pattern
     * 
     * @param string $param Parameter name
     * @param string $pattern A regular expression pattern without delimiters
     * @return void
     */
    public function pattern(string $param, string $pattern): void
    {
        Memory::registerPattern($param, $pattern);
    }

    /**
     * Set a global validation constraint.
     * 
     * @param string $param Parameter name.
     * @param string $constraint A validation constraint.
     * @return void
     */
    public function validate(string $param, string $constraint): void
    {
        Memory::registerConstraint($param, $constraint);
    }

    /**
     * Is grouping
     * 
     * @return bool
     */
    public function isGrouping() : bool
    {
        return !!Memory::currentGroup();
    }

    /**
     * Create a new route
     * 
     * @param string $uri
     * @return \Clicalmani\Routing\Route
     */
    private function createRoute(string $uri) : Route
    {
        $builder = Config::route('default_builder');
        return (new $builder)->create($uri);
    }

    /**
     * Verify if route exists
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return bool
     */
    public function routeExists(Route $route) : bool
    {
        $builder = Config::route('default_builder');
        return (new $builder)->isBuilt($route);
    }

    /**
     * Register new route
     * 
     * @param string $verb
     * @param string $uri
     * @param mixed $callback
     * @param bool $bind
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    private function register(string $verb, string $uri, mixed $callback, ?bool $bind = true) : Validator|Group
    {
        $route = $this->createRoute($uri);
        $route->verb = $verb;
        $route->action = $callback;
        
        /**
         * |-----------------------------------------------------------------
         * | Group Parameters
         * |-----------------------------------------------------------------
         * Optional parameters needs to be grouped. Options must be permitted
         * to match route requirements.
         */
        if ( $route->seemsOptional() ) {
            
            /** @var \Clicalmani\Routing\Group */
            $old_group = Memory::currentGroup();
            
            /**
             * |------------------------------------------------------
             * | Create a subgroup
             * |------------------------------------------------------
             * The subgroup will contain the possible routes to satisfy
             * the current route uri requirements.
             */
            $subgroup = new Group;

            $old_group->shareResourcesWith($subgroup);
            $options = $route->getOptions();
            $route->makeRequired();
            
            if ($this->getClientVerb() === $verb && $old_group->controller) {
                if ( is_array($callback) ) $route->action = $callback;
                else $route->action = [$old_group->controller, $callback];
                $old_group->addRoute($route); // Create a route without optional parameters
            }
            
            $uri = $route->uri(); // Options should start from the current route uri.
            $uris = [];
            
            foreach ($options as $index => $segment) {
                $segment->makeRequired();
                $segment->setValidator(null);
                /** @var string */
                $name = $segment->name;
                $uris[] = "$uri/$name";
                $tmp = '';
                for ($j = $index + 1; $j < count($options); $j++) {
                    $oname = substr($options[$j]->name, 1);
                    $name = "$name/$oname";
                    $tmp .= "$name/$oname";
                    $uris[] = "$uri/$name";
                    $uris[] = "$uri/$tmp";
                }
                $uris[] = "$uri/$name";
            }
            
            $uris = array_unique($uris);
            
            foreach ($uris as $uri) 
                $this->__register($uri, $verb, $callback, $bind, $old_group, $subgroup);

            $subgroup->run();
            
            Memory::currentGroup($old_group); // Restore group
            $validator = $this->register($verb, $route->uri(), $callback, $bind);

            $subgroup->addRoute($validator->route); // Add route to the subgroup for validation
                                                    // Remember if validations are also present on the main
                                                    // group they will be applied.
            
            return $subgroup;
        }
        
        $validator = new Validator($route);

        if (TRUE === $bind) $validator->bind();

        if (Memory::isRecording()) Memory::record($route);
        
        return $validator;
    }

    private function __register(string $uri, string $verb, mixed $callback, bool $bind, Group $group, Group $subgroup) 
    {
        /**
         * Option validator
         * 
         * @var \Clicalmani\Routing\Validator
         */
        $validator = $this->register($verb, $uri, $callback, $bind);
        
        if ($this->getClientVerb() === $verb && $validator->route && $group) {
            
            $group->addRoute($validator->route); // Add route option to its own group for prefixing

            $subgroup->addRoute($validator->route);  // Add route option to the subgroup for validation
                                                     // Remember if validations are also present on the main
                                                     // roup they will be applied.
        }
    }

    /**
     * Create a resource route
     * 
     * @param string $resource
     * @param array $routes
     * @param string $controller
     * @return \Clicalmani\Routing\Resource
     */
    private function __createResource(string $resource, string $controller, array $routes) : Resource
    {
        $routines = new Resource;

        ( new Group(function() use($resource, $routes, $routines, $controller) {
            foreach ($routes as $method => $segs) {
                foreach ($segs as $action => $uri) {
                    $routines[] = $this->register($method, $this->__parseResourceUri($resource, $uri), [$controller, $action]);
                }
            }
        }) )->prefix(explode('.', $resource)[0]);

        return $routines;
    }

    /**
     * Create a resource URI
     * 
     * @param string $resource
     * @param string $uri
     * @return string
     */
    private function __parseResourceUri(string $resource, string $uri) : string
    {
        $arr = explode('.', $resource);
        $nested = @$arr[1] ?? '';

        $route_parameter_prefix = \Clicalmani\Foundation\Support\Facades\Config::route('parameter_prefix');

        $bindings = [
            '{id}' => $route_parameter_prefix.'id',
            '{?id}' => !empty($nested) ? $route_parameter_prefix.'id': '',
            '{nested}' => $nested,
            '{nid}' => !empty($nested) ? $route_parameter_prefix.'nid': ''
        ];

        foreach ($bindings as $key => $value) {
            $uri = str_replace($key, $value, $uri);
        }
        
        return sprintf('/%s', trim(preg_replace('/\/\//', '/', $uri), '/'));
    }

    /**
     * Revolve a named route. 
     * 
     * @param mixed ...$params 
     * @return mixed
     */
    public function resolve(mixed ...$params) : mixed
    {
        /**
         * The first parameter is the name of the route
         * 
         * @var string
         */
        $name = array_shift($params);
        
        if ($route = $this->findByName($name)) {

            /** @var \Clicalmani\Routing\Segment */
            foreach ($route as $index => $segment) {
                if ($segment->isParameter() && @$params[$index]) $segment->value = $params[$index];
            }
            
            if ($arr = $route->getSegmentsNames()) return join('/', $arr);

            return '/';
        }

        return null;
    }

    /**
     * Find a route by name
     * 
     * @param string $name Route name
     * @return \Clicalmani\Routing\Route|null
     */
    public function findByName(string $name) : mixed
    {
        /** @var \Clicalmani\Routing\Route */
        foreach (Memory::getRoutes() as $verb => $routes) {
            foreach ($routes as $route) {
                if ($route->name === $name OR $route->uri === trim($name, '/')) return $route;
            }
        }

        return null;
    }
}
