<?php
namespace Clicalmani\Routing;

use Clicalmani\Validation\Validator;

/**
 * SegmentValidator Class
 * 
 * @package clicalmani/routing 
 * @author @clicalmani
 */
class SegmentValidator implements Factory\RouteSegmentValidatorInterface
{
    /**
     * Parameter to be validated
     * 
     * @var array
     */
    private array $uris = [];

    public function __construct(private string $name, string $uri)
    {
        $this->uris = [
            $name => $uri
        ];
    }

    public function test(string &$value) : bool
    {
        $input[$this->name] = $value;
        if ($valid = (new Validator(Validator::ERROR_SILENCE))->sanitize($input, $this->uris)) {
            $value = $input[$this->name];
        }
        
        return $valid;
    }
}