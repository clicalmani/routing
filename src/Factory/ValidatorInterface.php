<?php
namespace Clicalmani\Routing\Factory;

interface ValidatorInterface
{
    /**
     * Bind the current route
     * 
     * @return void
     */
    public function bind() : void;

    /**
     * Validate numeric parameter's value.
     * 
     * @param string|array $params
     * @return self
     */
    public function whereNumber(string|array $params) : self;

    /**
     * Validate integer parameter's value.
     * 
     * @param string|array $params
     * @return self
     */
    public function whereInt(string|array $params) : self;

    /**
     * Validate float parameter's value
     * 
     * @param string|array $params
     * @return self
     */
    public function whereFloat(string|array $params) : self;

    /**
     * Validate parameter's against an enumerated values.
     * 
     * @param string|array $params
     * @param array $list Enumerated list
     * @return self
     */
    public function whereEnum(string|array $params, array $list = []) : self;

    /**
     * Validate a token
     * 
     * @param string|array $params
     * @return self
     */
    public function whereToken(string|array $params) : self;

    /**
     * Validate parameter's value against any validator.
     * 
     * @param string|array $params
     * @param string $uri
     * @return self
     */
    public function where(string|array $params, string $uri) : self;

    /**
     * Validate parameter's value against a regular expression.
     * 
     * @param string|array $params
     * @param string $pattern A regular expression pattern without delimeters. Back slash (/) character will be used as delimiter
     * @return self
     */
    public function wherePattern(string|array $params, string $pattern) : self;

    /**
     * Add a before navigation hook. The callback function is passed the current param value and returns a boolean value.
     * If the callback function returns false, the navigation will be canceled.
     * 
     * @param string $param
     * @param callable $callback A callback function to be executed before navigation. The function receive the parameter value
     * as it's unique argument and must return false to halt the navigation, or true otherwise.
     * @return self
     */
    public function guardAgainst(string $param, callable $callback) : self;

    /**
     * Define route middleware
     * 
     * @param string|string[] $name Middleware name all class
     * @return self
     */
    public function middleware(string|array $name_or_class) : self;

    /**
     * Define route name
     * 
     * @param string $name
     * @return void
     */
    public function name(string $name) : void;
}