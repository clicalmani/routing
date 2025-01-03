<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Providers\ServiceProvider;

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
     * API prefix
     * 
     * @return string
     */
    public function getApiPrefix() : string
    {
        return with(new \App\Providers\RouteServiceProvider)->getApiPrefix();
    }

    /**
     * Detect api gateway
     * 
     * @return bool
     */
    public function isApi() : bool
    {
        if ( inConsoleMode() && defined('CONSOLE_API_ROUTE') ) return true;
        
        $api = $this->getApiPrefix();
        
        return preg_match(
            "/^\/$api/", 
            current_route()
        );
    }

    /**
     * Group routes
     * 
     * @param mixed $parameters It can be one or two parameters. if one parameter is passed it must a callable value,
     * otherwise the first argument must be an array and the second a callable value
     * @return \Clicalmani\Routing\Group|null
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
        if ( isset($args['prefix']) AND $prefix = $args['prefix']) 
            return with( new Group($callback) )->prefix($prefix);
        
        // Middleware
        if ( isset($args['middleware']) AND $name = $args['middleware']) $this->middleware($name, $callback);
        
        return null;
    }

    /**
     * Attach a middleware
     * 
     * @param string $name_or_class
     * @param mixed $callback If omitted the middleware will be considered as an inline middleware
     * @return void
     */
    public function middleware(string $name_or_class, mixed $callback = null) : void
    {
        if ( $middleware = $this->getMiddleware($name_or_class) ) 
            $this->registerMiddleware($callback ? $callback: $middleware, $name_or_class);
        else
            throw new \Exception(
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
        Cache::registerPattern($param, $pattern);
    }

    /**
     * Is grouping
     * 
     * @return bool
     */
    public function isGrouping() : bool
    {
        return !!Cache::currentGroup();
    }

    /**
     * Create a new route
     * 
     * @param string $signature
     * @return \Clicalmani\Routing\Route
     */
    private function createRoute(string $signature) : Route
    {
        return (new Builder)->create($signature);
    }

    /**
     * Verify if route exists
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return bool
     */
    public function routeExists(Route $route) : bool
    {
        $count = 0;

        /** @var \Clicalmani\Routing\Route */
        foreach (Cache::getRoutesByVerb($route->verb) as $r) {
            if ($r->signature === $route->signature) $count++;
        }

        return $count > 1;
    }

    /**
     * Register new route
     * 
     * @param string $verb
     * @param string $signature
     * @param mixed $callback
     * @param bool $bind
     * @return \Clicalmani\Routing\Validator|\Clicalmani\Routing\Group
     */
    private function register(string $verb, string $signature, mixed $callback, ?bool $bind = true) : Validator|Group
    {
        $route = $this->createRoute($signature);
        $route->verb = $verb;
        
        if ( FALSE == $route->seemsOptional() ) {
            
            /**
             * Method action
             */
            if ( is_array($callback) AND count($callback) === 2 ) {
                $route->action = $callback;
            } 
            
            /**
             * Controller method action
             */
            elseif ( is_string($callback) && $callback ) {
                
                if ($group = Cache::currentGroup()) {
                    if ($group->controller) $route->action = [$group->controller, $callback];
                    else $route->action = [$callback, '__invoke'];
                }
            } 

            /**
             * Anonymous action
             */
            elseif ( is_callable($callback) ) $route->action = $callback;
            
            /**
             * Controller class action
             */
            elseif (!$callback) {
                $route->action = '__invoke';
            }
            
            if ($this->isGrouping() && $this->getClientVerb() === $verb) {
                $group = Cache::currentGroup();
                $group->addRoute($route);
            }
        }

        /**
         * |-----------------------------------------------------------------
         * | Group Parameters
         * |-----------------------------------------------------------------
         * Optional parameters needs to be grouped. Options must be permitted
         * to match route requirements.
         */
        else {
            
            /** @var \Clicalmani\Routing\Group */
            $old_group = Cache::currentGroup();
            
            /**
             * |------------------------------------------------------
             * | Create a subgroup
             * |------------------------------------------------------
             * The subgroup will contain the possible routes to satisfy
             * the current route signature requirements.
             */
            $subgroup = new Group;

            if ($old_group->controller) $subgroup->controller = $old_group->controller;

            $options = $route->getOptions();
            $route->makeRequired();
            
            if ($this->isGrouping() && $this->getClientVerb() === $verb && $old_group) {
                
                $route->action = [$old_group->controller, $callback];

                $old_group->addRoute($route); // Add route to its own group for prefixing
            }

            $signature = $route->getSignature(); // Options should start from the current route signature.
            $setValidator = function(string $signature) use($verb, $callback, $bind, $old_group, $subgroup) {
                /**
                 * Option validator
                 * 
                 * @var \Clicalmani\Routing\Validator
                 */
                $validator = $this->register($verb, $signature, $callback, $bind);
                
                if ($this->isGrouping() && $this->getClientVerb() === $verb && $validator->route && $old_group) {
                    
                    $old_group->addRoute($validator->route); // Add route option to its own group for prefixing

                    $subgroup->addRoute($validator->route);  // Add route option to the subgroup for validation
                                                             // Remember if validations are also present on the main
                                                             // roup they will be applied.
                }
            };

            $signatures = [];
            
            foreach ($options as $index => $path) {
                $path->makeRequired();
                $path->setValidator(null);
                /** @var string */
                $name = $path->name;
                $signatures[] = "$signature/$name";
                for ($j = $index + 1; $j < count($options); $j++) {
                    $name = "$name/" . substr($options[$j]->name, 1);
                }
                $signatures[] = "$signature/$name";
            }

            array_pop($signatures);

            foreach ($signatures as $signature) $setValidator($signature);

            $subgroup->run();
            
            Cache::currentGroup($old_group); // Restore group
            $validator = $this->register($verb, $route->getSignature(), $callback, $bind);

            $subgroup->addRoute($validator->route); // Add route to the subgroup for validation
                                                    // Remember if validations are also present on the main
                                                    // group they will be applied.
            
            return $subgroup;
        }
        
        $validator = new Validator($route);

        if (TRUE == $bind) $validator->bind();

        if (Cache::isRecording()) Cache::record($route);
        
        return $validator;
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

            /** @var \Clicalmani\Routing\Path */
            foreach ($route as $index => $path) {
                if ($path->isParameter() && @$params[$index]) $path->value = $params[$index];
            }
            
            if ($arr = $route->getPathNameArray()) return join('/', $arr);

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
        foreach (Cache::getRoutes() as $verb => $routes) {
            foreach ($routes as $route) {
                if ($route->name === $name OR $route->signature === trim($name, '/')) return $route;
            }
        }

        return null;
    }
}
