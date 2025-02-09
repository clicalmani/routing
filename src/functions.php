<?php
namespace Clicalmani\Routing;

/**
 * This function processes a given value and returns an array containing the controller class and method.
 * 
 * @param string|array $value
 * @return array
 */
function action(string|array $value) : array {
    if ( is_string($value) ) {
        if (preg_match('/^([A-Za-z0-9_]+)@([A-Za-z0-9_]+)$/', $value, $matches)) {
            return ["\\App\\Http\\Controllers\\" . $matches[1], $matches[2]];
        }
    }

    if ( is_array($value) && count($value) === 2 ) return [$value[0], $value[1]];

    return [];
}