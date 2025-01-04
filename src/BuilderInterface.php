<?php
namespace Clicalmani\Routing;

interface BuilderInterface
{
    /**
     * Build the requested route. 
     * 
     * @return \Clicalmani\Routing\Route|null
     */
    public function getRoute() : Route|null;
}
