<?php 
namespace Clicalmani\Routing;

/**
 * Validator Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Validator
{
    /**
     * Controller
     * 
     * @param \Clicalmani\Routing\Route $route
     */
    public function __construct(private Route $route) 
    {
        /**
         * Validate global patterns
         */
        foreach (Memory::getGlobalPatterns() as $param => $pattern) {
            /** @var \Clicalmani\Routing\Segment */
            foreach ($this->route as $segment) {
                if ($segment->getName() === $param) {
                    $segment->setValidator(new SegmentValidator($param, 'regexp|pattern:' . $pattern));
                }
            }
        }
    }

    /**
     * @override Getter
     */
    public function __get(mixed $parameter)
    {
        switch ($parameter) {
            case 'route': return $this->route;
        }
    }

    /**
     * @override Setter
     */
    public function __set(mixed $parameter, mixed $value)
    {
        switch ($parameter) {
            case 'route': $this->route = $value; break;
        }
    }

    /**
     * Check for duplicate routes and define route uri
     * 
     * @return void
     */
    public function bind() : void
    {
        Memory::addRoute($this->route);
    }

    /**
     * Revalidate a parameter
     * 
     * @param string $param
     * @param string $pattern
     * @return void
     */
    private function revalidateParam(string $param, string $pattern) : void
    {
        /** @var \Clicalmani\Routing\Segment */
        foreach ($this->route as $segment) {
            if ($segment->getName() === $param) $segment->setValidator(new SegmentValidator($param, $pattern));
        }
    }

    /**
     * Validate numeric parameter's value.
     * 
     * @param string|array $params
     * @return static
     */
    public function whereNumber(string|array $params) : static
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'numeric');
        
        return $this;
    }

    /**
     * Validate integer parameter's value.
     * 
     * @param string|array $params
     * @return static
     */
    public function whereInt(string|array $params) : static
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'int');
        
        return $this;
    }

    /**
     * Validate float parameter's value
     * 
     * @param string|array $params
     * @return static
     */
    public function whereFloat(string|array $params) : static
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'float');
        
        return $this;
    }

    /**
     * Validate parameter's against an enumerated values.
     * 
     * @param string|array $params
     * @param ?array $list Enumerated list
     * @return static
     */
    public function whereEnum(string|array $params, ?array $list = []) : static
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'enum|list:' . join(',', $list));
        
        return $this;
    }

    /**
     * Validate a token
     * 
     * @param string|array $params
     * @return static
     */
    public function whereToken(string|array $params) : static
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'token');
        
        return $this;
    }

    /**
     * Validate parameter's value against any validator.
     * 
     * @param string|array $params
     * @param string $uri
     * @return static
     */
    public function where(string|array $params, string $uri) : static
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, $uri);
        
        return $this;
    }

    /**
     * Validate parameter's value against a regular expression.
     * 
     * @param string|array $params
     * @param string $pattern A regular expression pattern without delimeters. Back slash (/) character will be used as delimiter
     * @return static
     */
    public function wherePattern(string|array $params, string $pattern) : static
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'regexp|pattern:' . $pattern);
        
        return $this;
    }

    /**
     * Add a before navigation hook. The callback function is passed the current param value and returns a boolean value.
     * If the callback function returns false, the navigation will be canceled.
     * 
     * @param string $param
     * @param callable $callback A callback function to be executed before navigation. The function receive the parameter value
     * as it's unique argument and must return false to halt the navigation, or true otherwise.
     * @return static
     */
    public function guardAgainst($param, $callback) : static
    {
        $uid = uniqid('gard-');
        
        Memory::addGuard($uid, $param, $callback);
        $this->revalidateParam($param, 'nguard|uid:' . $uid);

        return $this;
    } 

    /**
     * Define route middleware
     * 
     * @param string|string[] $name Middleware name all class
     * @return static
     */
    public function middleware(string|array $name_or_class) : static
    {
        $name_or_class = (array) $name_or_class;

        foreach ($name_or_class as $name) $this->route->addMiddleware($name);

        return $this;
    }

    /**
     * Define route name
     * 
     * @param string $name
     * @return void
     */
    public function name(string $name) : void
    {
        $this->route->name = $name;

        if ($this->route->isDoubled()) {

            $this->route->name = ''; // Undo renaming

            throw new \Exception(
                sprintf("There is an existing route with the same name %s.", $name)
            );
        }
    }
}
