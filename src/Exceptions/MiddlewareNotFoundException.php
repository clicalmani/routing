<?php
namespace Clicalmani\Routing\Exceptions;

class MiddlewareNotFoundException extends \Exception
{
    public function __construct(string $message = "Middleware not found", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}