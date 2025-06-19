<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Support\Facades\Config;

/**
 * Segment Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Segment implements Factory\RouteSegmentInterface
{
    /**
     * Segment name
     * 
     * @var string
     */
    private string $name;

    /**
     * Segment value
     * 
     * @var ?string
     */
    private ?string $value = null;

    /**
     * Segment value
     * 
     * @var ?\Clicalmani\Routing\SegmentValidator
     */
    private ?SegmentValidator $validator = null;

    public function isParameter() : bool
    {
        return !!preg_match("/^" . Config::route('parameter_prefix') . "/", $this->name);
    }

    public function isValidable() : bool
    {
        return !!$this->validator;
    }

    public function getName() : string|false
    {
        if (FALSE === $this->isParameter()) return $this->name;

        return substr($this->name, 1);
    }

    public function isValid() : bool
    {
        if (!$this->validator) return true;
        $value = (string) $this->value;
        return $this->validator->test($value);
    }

    public function isOptional() : bool
    {
        return preg_match("/^\?" . Config::route('parameter_prefix') . "/", $this->name);
    }

    public function makeRequired() : void
    {
        $this->name = str_replace('?', '', $this->name);
    }

    /**
     * Make the segment available in global variables such as $_GET, $_POST
     * $_REQUEST as a PHP parameter.
     * 
     * @return void
     */
    public function register() : void
    {
        /**
         * Make the parameter available to the request.
         * We use the global $_REQUEST array so that the parameter can be access through global variables 
         * such as $_GET and $_POST
         */
        $_REQUEST[$this->getName()] = $this->value;
    }

    public function setValidator(?SegmentValidator $validator) : void
    {
        $this->validator = $validator;
    }

    public function equals(Segment $segment) : bool
    {
        return $segment->getName() === $this->getName();
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'value': return $this->value;
            case 'name': return $this->name;
        }
    }

    public function __set(string $name, mixed $value)
    {
        switch ($name) {
            case 'value': $this->value = $value; break;
            case 'name': $this->name = $value; break;
        }
    }
}
