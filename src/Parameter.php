<?php
namespace Clicalmani\Routing;

class Parameter
{
    /**
     * Parameter name
     * 
     * @var string
     */
    public string $name;

    /**
     * Parameter value
     * 
     * @var mixed
     */
    public mixed $value;

    /**
     * Parameter position
     * 
     * @var int
     */
    public int $position;
}
