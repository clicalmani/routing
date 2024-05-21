<?php
namespace Clicalmani\Routing;

/**
 * Path Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class Path 
{
    /**
     * Path name
     * 
     * @var string
     */
    private string $name;

    /**
     * Path value
     * 
     * @var string|null
     */
    private string|null $value = null;

    /**
     * Path value
     * 
     * @var \Clicalmani\Routing\PathValidator|null
     */
    private PathValidator|null $validator = null;

    /**
     * Is parameter
     * 
     * @return bool
     */
    public function isParameter() : bool
    {
        return !!preg_match('/^:/', $this->name);
    }

    /**
     * Check if path has a validator.
     * 
     * @return bool
     */
    public function isValidable() : bool
    {
        return !!$this->validator;
    }

    /**
     * Get path name
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

        $value = (string)$this->value;
        $valid = $this->validator->test($value);
        $this->value = $value;

        return $valid;
    }

    /**
     * Check optional path
     * 
     * @return bool
     */
    public function isOptional() : bool
    {
        return preg_match('/^\?:/', $this->name);
    }

    /**
     * Make an optional path required.
     * 
     * @return void
     */
    public function makeRequired() : void
    {
        $this->name = str_replace('?', '', $this->name);
    }

    /**
     * Make the path available in global variables such as $_GET, $_POST
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
     * Remove path validator
     * 
     * @param \Clicalmani\Routing\PathValidator|null $validator
     * @return void
     */
    public function setValidator(PathValidator|null $validator) : void
    {
        $this->validator = $validator;
    }

    /**
     * Compare the given path to the current one.
     * 
     * @param \Clicalmani\Routing\Path $path
     * @return bool
     */
    public function equals(Path $path) : bool
    {
        return $path->getName() === $this->getName();
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
