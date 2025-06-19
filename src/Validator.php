<?php 
namespace Clicalmani\Routing;

/**
 * Validator Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Validator implements Factory\ValidatorInterface
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

    public function whereNumber(string|array $params) : self
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'numeric');
        
        return $this;
    }

    public function whereInt(string|array $params) : self
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'int');
        
        return $this;
    }

    public function whereFloat(string|array $params) : self
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'float');
        
        return $this;
    }

    public function whereEnum(string|array $params, array $list = []) : self
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'enum|list:' . join(',', $list));
        
        return $this;
    }

    public function whereToken(string|array $params) : self
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'token');
        
        return $this;
    }

    public function where(string|array $params, string $uri) : self
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, $uri);
        
        return $this;
    }

    public function wherePattern(string|array $params, string $pattern) : self
    {
        $params = (array)$params;

        foreach ($params as $param) $this->revalidateParam($param, 'regexp|pattern:' . $pattern);
        
        return $this;
    }

    public function guardAgainst(string $param, callable $callback) : self
    {
        $uid = uniqid('gard-');
        
        Memory::addGuard($uid, $param, $callback);
        $this->revalidateParam($param, 'nguard|uid:' . $uid);

        return $this;
    } 

    public function middleware(string|array $name_or_class) : self
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
