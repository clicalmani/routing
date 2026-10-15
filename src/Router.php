<?php
namespace Clicalmani\Routing;

use Clicalmani\Core\Http\Middlewares\MiddlewareAccessor;
use Clicalmani\Core\Http\Middlewares\MiddlewareInterface;
use Clicalmani\Core\Providers\ServiceProvider;
use Clicalmani\Core\Support\Facades\Config;
use Clicalmani\Core\Support\Facades\Str;

/**
 * Routing Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Router implements Factory\RouterInterface
{
    use Method;

    public function getClientVerb() : string
    {
        return strtolower( (string) @ $_SERVER['REQUEST_METHOD'] );
    }

    public function gateway() : string
    {
        if ($this->isApi()) return 'api';
        return 'web';
    }

    public function isApi() : bool
    {
        if ( isConsoleMode() && defined('CONSOLE_API_ROUTE') ) return true;
        
        $api = \Clicalmani\Core\Support\Facades\Config::route('api_prefix');
        
        return preg_match(
            "/^\/$api/", 
            client_url()
        );
    }

    public function isBroadcasting() : bool
    {
        $prefix = Config::broadcasting('auth_prefix');

        if (!$prefix) return false;
        
        return !!preg_match("/^\/" . preg_quote($prefix, '/') . "/", client_url());
    }

    public function group(mixed ...$parameters) : ?\Clicalmani\Routing\Group
    {
        if (count($parameters) === 1) return new Group($parameters[0]);

        [$args, $callback] = [$parameters[0] ?? [], $parameters[1] ?? null];

        $middleware = $args['middleware'] ?? null;
        $names = array_values(array_filter(
            is_string($middleware) ? explode('|', $middleware) : (array) $middleware
        ));
        
        $parent = Registry::currentGroup();
        foreach ($names as $name) $this->middleware($name);
        Registry::setCurrentGroup($parent);

        $group = new Group($callback, $names ? implode('|', $names) : null);

        if (!empty($args['prefix'])) $group->prefix($args['prefix']);

        foreach ((array) ($args['where'] ?? []) as $param => $rule) $group->where($param, $rule);

        return $group;
    }

    public function middleware(string $name_or_class, mixed $callback = null) : Group
    {
        if ( $middleware = $this->getMiddleware($name_or_class) ) {
            $this->registerMiddleware($callback ? $callback: $middleware, $name_or_class);
            return new Group($callback, $name_or_class);
        } else
            throw new Exceptions\MiddlewareNotFoundException(
                sprintf("Unknow middleware %s specified", $name_or_class)
            );
    }

    /**
     * Get a middleware by name or class
     * 
     * @param string $name_or_class
     * @return ?MiddlewareInterface
     */
    private function getMiddleware(string $name_or_class) : ?MiddlewareInterface
    {
        return MiddlewareAccessor::getMiddleware(
            $this->parseMiddleware($name_or_class)['main']
        );
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
     * @return array
     */
    private function parseMiddleware(string $name): array
    {
        $arr = preg_split('/[,]/', strtr($name, '@[]', ',,,'), -1, PREG_SPLIT_NO_EMPTY);
        $main = array_shift($arr);

        return [
            'main' => $main,
            'subs' => collection($arr)->map(fn(string $name) => trim($name))->toArray()
        ];
    }

    public function pattern(string $param, string $pattern): void
    {
        Registry::registerPattern($param, $pattern);
    }

    public function validate(string|array $param, string|array $constraint): void
    {
        Registry::registerConstraint($param, $constraint);
    }

    public function isGrouping() : bool
    {
        return !!Registry::currentGroup();
    }

    /**
     * Create a new route
     * 
     * @param string $uri
     * @return ?\Clicalmani\Routing\Route
     */
    private function createRoute(string $uri) : ?Route
    {
        if ($builder = Config::route('default_builder')) {
            return (new $builder)->create($uri);
        }
        
        return null;
    }

    /**
     * Verify if route exists
     * 
     * @param \Clicalmani\Routing\Route $route
     * @return bool
     */
    public function routeExists(Route $route) : bool
    {
        if ($builder = Config::route('default_builder')) {
            return (new $builder)->isBuilt($route);
        }
        
        return false;
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

        /**
         * |-----------------------------------------------------------------
         * | Group Parameters
         * |-----------------------------------------------------------------
         * Optional parameters needs to be grouped. Options must be permitted
         * to match route requirements.
         */
        if ( $route->seemsOptional() ) {
            $segments  = $route->getSegments();
            $old_group = Registry::currentGroup();          // peut être null
            $subgroup  = new Group;                         // devient le groupe courant

            $old_group?->shareResourcesWith($subgroup);
            $route->makeRequired();

            foreach ($this->expandOptionalUris($segments) as $variant) {
                $this->__register($variant, $verb, $callback, $bind, $old_group);
            }

            $subgroup->run();   
            Registry::currentGroup($old_group); // restaure le groupe parent
            $validator = $this->register($verb, $route->uri(), $callback, $bind);
            $subgroup->addRoute($validator->route);

            return $subgroup;
        }

        $route->verb = $verb;
        $route->action = $callback;
        
        $validator = new Validator($route);

        if (TRUE === $bind) $validator->bind();
        
        // Register route
        if (Registry::isRecording()) Registry::record($route);
        
        return $validator;
    }

    private function __register(string $uri, string $verb, mixed $callback, bool $bind, ?Group $group) : void
    {
        $validator = $this->register($verb, $uri, $callback, $bind);

        // Le setter d'action l'a déjà ajoutée au sous-groupe (groupe courant).
        if ($group && $this->getClientVerb() === $verb) $group->addRoute($validator->route);
    }

    private function expandOptionalUris(array $segments) : array
    {
        $prefix   = Config::route('parameter_prefix') ?: ':';
        $optional = [];
        foreach ($segments as $i => $s) if ($s->isOptional()) $optional[] = $i;

        $uris = [];
        for ($mask = 1; $mask < (1 << count($optional)); $mask++) {   // 0 = route sans optionnels, déjà gérée
            $names = $shape = [];
            foreach ($segments as $i => $s) {
                $pos = array_search($i, $optional, true);
                if ($pos !== false && !($mask & (1 << $pos))) continue;

                $name    = preg_replace('/^\?/', '', $s->name);
                $names[] = $name;
                $shape[] = str_starts_with($name, $prefix) ? $prefix : $name;
            }
            $uris[implode('/', $shape)] ??= '/' . implode('/', $names);   // 1re variante gagnante = la plus à gauche
        }

        $uris = array_values($uris);
        usort($uris, fn($a, $b) => substr_count($b, $prefix) <=> substr_count($a, $prefix));

        return $uris;
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
        $routines = new Resource($resource);

        ( new Group(function() use($resource, $routes, $routines, $controller) {
            foreach ($routes as $verb => $segs) {
                foreach ($segs as $action => $uri) {
                    $routines[] = $this->register($verb, $this->__parseResourceUri($resource, $uri), [$controller, $action]);
                }
            }
        }) );

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
        [$main, $nested] = [array_shift($arr), (isset($arr[0]) ? $arr[0]: '')];

        $uri = str_replace('{resource}', $main, $uri);
        
        $route_parameter_prefix = \Clicalmani\Core\Support\Facades\Config::route('parameter_prefix');

        $bindings = [
            '{id}' => $route_parameter_prefix . Str::singularize($main),
            '{?id}' => !empty($nested) ? '?' . $route_parameter_prefix . Str::singularize($main): '',
            '{nested}' => $nested,
            '{nid}' => !empty($nested) ? $route_parameter_prefix . Str::singularize($nested): ''
        ];

        foreach ($bindings as $key => $value) {
            $uri = str_replace($key, $value, $uri);
        }
        
        return sprintf('/%s', trim(preg_replace('#/+#', '/', $uri), '/'));
    }

    public function resolve(mixed ...$params) : mixed
    {
        $name   = (string) array_shift($params);
        $routes = $this->routesNamed($name);

        if (!$routes) return null;

        $first = $params[0] ?? null;
        $assoc = is_array($first) && !array_is_list($first);
        $given = is_array($first) ? $first : $params;

        // Une valeur null ou vide équivaut à un paramètre absent.
        $blank = fn(mixed $value) => $value === null || $value === '';

        if ($assoc) {
            $given = array_filter($given, fn($value) => !$blank($value));
        } else {
            while ($given && $blank(end($given))) array_pop($given);
        }

        // Variante retenue : celle qui consomme le plus de paramètres fournis.
        // À égalité, la première (GET d'abord, puis ordre d'enregistrement).
        $chosen = null;

        foreach ($routes as $route) {
            $names = $this->parameterNames($route);

            if ($assoc) {
                if (array_diff($names, array_keys($given))) continue;       // un paramètre manque
                $map = array_intersect_key($given, array_flip($names));
            } else {
                if (count($names) > count($given)) continue;
                $map = $names ? array_combine($names, array_slice($given, 0, count($names))) : [];
            }

            if (!$chosen || count($map) > count($chosen[1])) $chosen = [$route, $map];
        }

        if (!$chosen) {
            // La variante la plus courte donne la liste minimale des paramètres requis.
            $base = $routes[0];
            foreach ($routes as $route) {
                if (count($this->parameterNames($route)) < count($this->parameterNames($base))) $base = $route;
            }

            $required = $this->parameterNames($base);
            $missing  = $assoc ? array_diff($required, array_keys($given)) : array_slice($required, count($given));

            throw new \InvalidArgumentException(
                sprintf('Missing parameter(s) "%s" for route "%s".', implode('", "', $missing), $name)
            );
        }

        [$route, $map] = $chosen;

        // Un paramètre connu d'une variante mais inutilisable avec celle retenue (y sans x).
        if ($assoc) {
            $known = array_merge(...array_map(fn(Route $r) => $this->parameterNames($r), $routes));
            $stray = array_diff(array_intersect(array_keys($given), $known), array_keys($map));

            if ($stray) {
                throw new \InvalidArgumentException(sprintf(
                    'Parameter(s) "%s" cannot be used without the optional parameters that precede them (route "%s").',
                    implode('", "', $stray),
                    $name
                ));
            }
        }

        $url = '';

        foreach ($route as $segment) {
            $url .= '/' . ($segment->isParameter() ? rawurlencode((string) $map[$segment->getName()]) : $segment->name);
        }

        return $url ?: '/';
    }

    public function findByName(string $name) : ?\Clicalmani\Routing\Route
    {
        /** @var \Clicalmani\Routing\Route */
        foreach (Registry::getRoutes() as $verb => $routes) {
            foreach ($routes as $route) {
                if ($route->name === $name OR $route->uri === trim($name, '/')) return $route;
            }
        }

        return null;
    }

    private function routesNamed(string $name) : array
    {
        if ($name === '') return [];

        $found = [];

        foreach (Registry::getRoutes() as $verb => $routes) {
            foreach ($routes as $route) {
                if ($route->name === $name || $route->uri === trim($name, '/')) $found[$verb][] = $route;
            }
        }

        // Une URL générée sert à naviguer : on préfère la variante GET.
        return array_merge($found['get'] ?? [], ...array_values(array_diff_key($found, ['get' => true])));
    }

    /** @return string[] */
    private function parameterNames(Route $route) : array
    {
        return array_map(fn(Segment $segment) => $segment->getName(), $route->getParameters());
    }
}
