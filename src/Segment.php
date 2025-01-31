<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Support\Facades\Config;

/**
 * Segment Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Segment 
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
     * @var string|null
     */
    private string|null $value = null;

    /**
     * Segment value
     * 
     * @var \Clicalmani\Routing\SegmentValidator|null
     */
    private SegmentValidator|null $validator = null;

    /**
     * Is parameter
     * 
     * @return bool
     */
    public function isParameter() : bool
    {
        return !!preg_match("/^" . Config::route('parameter_prefix') . "/", $this->name);
    }

    /**
     * Check if segment has a validator.
     * 
     * @return bool
     */
    public function isValidable() : bool
    {
        return !!$this->validator;
    }

    /**
     * Get segment name
     * 
     * @return string|false
     */
    public function getName() : string|false
    {
        if (FALSE === $this->isParameter()) return $this->name;

        return substr($this->name, 1);
    }

    /**
     * Validate a parameter
     * 
     * @return bool true on success, false on failure
     */
    public function isValid() : bool
    {
        if (!$this->validator) return true;
        $value = (string) $this->value;
        return $this->validator->test($value);
    }

    /**
     * Check optional segment
     * 
     * @return bool
     */
    public function isOptional() : bool
    {
        return preg_match("/^\?" . Config::route('parameter_prefix') . "/", $this->name);
    }

    /**
     * Make an optional segment required.
     * 
     * @return void
     */
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

    /**
     * Set segment validator
     * 
     * @param \Clicalmani\Routing\SegmentValidator|null $validator
     * @return void
     */
    public function setValidator(SegmentValidator|null $validator) : void
    {
        $this->validator = $validator;
    }

    /**
     * Compare the given segment to the current one.
     * 
     * @param \Clicalmani\Routing\Segment $segment
     * @return bool
     */
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
