<?php
namespace Clicalmani\Routing;

use Clicalmani\Validation\Validator;

/**
 * SegmentValidator Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class SegmentValidator extends Validator
{
    /**
     * Parameter to be validated
     * 
     * @var array
     */
    private array $uris = [];

    public function __construct(private string $name, string $uri)
    {
        parent::__construct(true);
        
        $this->uris = [
            $name => $uri
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
        $valid = parent::sanitize($input, $this->uris);
        
        if ( TRUE == $valid ) $value = $input[$this->name];
        
        return $valid;
    }
}