<?php
namespace Clicalmani\Routing\Factory;

interface RouteInterface
{
    /**
     * Returns route URI
     * 
     * @return string
     */
    public function uri() : string;

    /**
     * Set route URI
     * 
     * @param string $new_uri
     * @return void
     */
    public function setUri(string $new_uri) : void;

    /**
     * Reset route URI
     * 
     * @return void
     */
    public function resetUri() : void;

    /**
     * Remove route segment at the specified index.
     * 
     * @param int $index
     * @param bool $preserve_keys Preserve array keys, Default true
     * @return void
     */
    public function removeSegmentAt(int $index, bool $preserve_keys = true) : void;

    /**
     * Find the difference of two routes.
     * 
     * @param self $route
     * @return string[]
     */
    public function diff(\Clicalmani\Routing\Route $route) : array;

    /**
     * Returns an array of route segments' names.
     * 
     * @return string[]
     */
    public function getSegmentsNames() : array;

    /**
     * Compare the given route to the current route.
     * 
     * @param self $route
     * @return bool
     */
    public function equals(\Clicalmani\Routing\Route $route) : bool;

    /**
     * Check if there is one or more optional parameters.
     * 
     * @return bool
     */
    public function seemsOptional() : bool;

    /**
     * Returns optional segments
     * 
     * @return \Clicalmani\Routing\Segment[]
     */
    public function getOptions() : array;

    /**
     * Returns route segments
     * 
     * @return \Clicalmani\Routing\Segment[]
     */
    public function getSegments() : array;

    /**
     * Remove all optional segments
     * 
     * @return void
     */
    public function makeRequired() : void;

    /**
     * Get route parameters
     * 
     * @return \Clicalmani\Routing\Segment[]
     */
    public function getParameters() : array;

    /**
     * Add a new middleware
     * 
     * @param mixed $name_or_class
     * @return void
     */
    public function addMiddleware(mixed $name_or_class) : void;

    /**
     * Remove a middleware
     * 
     * @param mixed $name_or_class
     * @return void
     */
    public function excludeMiddleware(mixed $name_or_class) : void;

    /**
     * Get route middlewares
     * 
     * @return array
     */
    public function getMiddlewares() : array;

    /**
     * Verfify if route is authorized
     * 
     * @return int|bool
     */
    public function isAuthorized(?\Clicalmani\Foundation\Http\Requests\RequestInterface $request = null) : int|bool;

    /**
     * Verify for an existing named route with the same name.
     * 
     * @return bool
     */
    public function isDoubled() : bool;

    /**
     * Before navigation hook
     * 
     * @return ?callable
     */
    public function beforeHook(?callable $hook = null) : ?callable;

    /**
     * After navigation hook
     * 
     * @return ?callable
     */
    public function afterHook(?callable $hook = null) : ?callable;

    /**
     * Missing callback
     * 
     * @param ?callable $callback
     * @return mixed
     */
    public function missing(?callable $callback = null) : mixed;

    /**
     * Order route result
     * 
     * @param ?string $orderBy
     * @return mixed
     */
    public function orderResultBy(?string $orderBy = null) : mixed;

    /**
     * Distinct result
     * 
     * @param ?bool $distinct
     * @return mixed
     */
    public function distinctResult(?bool $distinct = null) : mixed;

    /**
     * Limit result set
     * 
     * @param int $offset
     * @param int $row_count
     * @return mixed
     */
    public function limitResult(int $offset = 0, int $row_count = 0) : mixed;

    /**
     * Enable SQL CALC_FOUND_ROWS on the request query.
     * 
     * @param ?bool $calc
     * @return mixed
     */
    public function calcFoundRows(?bool $calc = null) : mixed;

    /**
     * Specify the table to delete from when deleting from
     * multiple tables.
     * 
     * @param ?string $table
     * @return mixed
     */
    public function deleteFrom(?string $table = null) : mixed;

    /**
     * Ignore primary key duplic warning
     * 
     * @param ?bool $ignore
     * @return mixed
     */
    public function ignoreKeyWarning(?bool $ignore = null) : mixed;

    /**
     * Scope a resource route.
     * 
     * @param array $scope
     * @return mixed
     */
    public function scoped(array $scope = []) : mixed;

    /**
     * Check custom route
     * 
     * @return bool
     */
    public function isCustom() : bool;

    /**
     * Check if route is named
     * 
     * @param string $name
     * @return bool
     */
    public function named(string $name) : bool;

    /**
     * Check if the route matches the given URI.
     * 
     * @param string $uri
     * @return bool
     */
    public function is(string $uri) : bool;
}