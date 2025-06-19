<?php
namespace Clicalmani\Routing\Factory;

interface RouteSegmentInterface
{
    /**
     * Is parameter
     * 
     * @return bool
     */
    public function isParameter() : bool;

    /**
     * Check if segment has a validator.
     * 
     * @return bool
     */
    public function isValidable() : bool;

    /**
     * Get segment name
     * 
     * @return string|false
     */
    public function getName() : string|false;

    /**
     * Validate a parameter
     * 
     * @return bool true on success, false on failure
     */
    public function isValid() : bool;

    /**
     * Check optional segment
     * 
     * @return bool
     */
    public function isOptional() : bool;

    /**
     * Make an optional segment required.
     * 
     * @return void
     */
    public function makeRequired() : void;

    /**
     * Make the segment available in global variables such as $_GET, $_POST
     * $_REQUEST as a PHP parameter.
     * 
     * @return void
     */
    public function register() : void;

    /**
     * Set segment validator
     * 
     * @param ?\Clicalmani\Routing\SegmentValidator $validator
     * @return void
     */
    public function setValidator(?\Clicalmani\Routing\SegmentValidator $validator) : void;

    /**
     * Compare the given segment to the current one.
     * 
     * @param \Clicalmani\Routing\Segment $segment
     * @return bool
     */
    public function equals(\Clicalmani\Routing\Segment $segment) : bool;
}