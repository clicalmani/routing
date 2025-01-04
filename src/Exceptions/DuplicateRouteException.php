<?php
namespace Clicalmani\Routing\Exceptions;

class DuplicateRouteException extends \Exception
{
    public function __construct(\Clicalmani\Routing\Route $route)
    {
        parent::__construct(sprintf("Duplicate route %s", $route));
    }
}