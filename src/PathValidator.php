<?php
namespace Clicalmani\Routing;

use Clicalmani\Foundation\Validation\InputValidator;

/**
 * PathValidator Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class PathValidator extends InputValidator
{
    /**
     * Parameter to be validated
     * 
     * @var array
     */
    private array $signatures = [];

    public function __construct(private string $name, string $signature)
    {
        parent::__construct(true);
        
        $this->signatures = [
            $name => $signature
        ];
    }

    /**
     * Test a value or fail
     * 
     * @param string &$value
     * @return bool
     */
    public function test(string &$value) : bool
    {
        $input[$this->name] = $value;
        $valid = parent::sanitize($input, $this->signatures);
        
        if ( TRUE == $valid ) $value = $input[$this->name];
        
        return $valid;
    }
}