<?php
namespace Clicalmani\Routing\Factory;

interface RouteSegmentValidatorInterface
{
    /**
     * Test a value or fail
     * 
     * @param string &$value
     * @return bool
     */
    public function test(string &$value) : bool;
}