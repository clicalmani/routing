<?php
namespace Clicalmani\Routing\Factory;

interface RouteResourceInterface
{
    /**
     * Override the default not found behaviour.
     * 
     * @param callable $closure A closure function that returns the response type.
     * @return self
     */
    public function missing(callable $closure) : self;

    /**
     * Show distinct rows on resource view
     * 
     * @param bool $enable
     * @return self
     */
    public function distinct(bool $enable = false) : self;

    /**
     * Ignore primary key duplicate warning
     * 
     * @param bool $enable
     * @return self
     */
    public function ignore(bool $enable = false) : self;

    /**
     * From statement when deleting from multiple tables
     * 
     * @param string $table
     * @return self
     */
    public function from(string $table) : self;

    /**
     * Enable SQL CAL_FOUND_ROWS
     * 
     * @param bool $enable
     * @return self
     */
    public function calcRows(bool $enable = false) : self;

    /**
     * Limit number of rows in the result set
     * 
     * @param int $offset
     * @param int $row_count
     * @return self
     */
    public function limit(int $offset = 0, int $row_count = 0) : self;

    /**
     * Order by
     * 
     * @param string $order
     * @return self
     */
    public function orderBy(string $order = 'NULL') : self;

    /**
     * @return self
     */
    public function middleware(string $name_or_class) : self;

    /**
     * Filter the resource to only include specified actions.
     * 
     * @param string|array $action
     * @return self
     */
    public function only(string|array $action) : self;

    /**
     * Filter the resource to exclude specified actions.
     * 
     * @param string|array $action
     * @return self
     */
    public function except(string|array $action) : self;

    /**
     * Scope the resource routes.
     * 
     * @param array $scope
     * @return self
     */
    public function scoped(array $scope) : self;

    /**
     * Defines shallow nested routes.
     * 
     * @return self
     */
    public function shallow() : self;

    /**
     * Set custom names for the resource routes.
     * 
     * @param array $custom_names
     * @return self
     */
    public function names(array $custom_names) : self;
}