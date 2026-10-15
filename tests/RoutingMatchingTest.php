<?php
namespace Routing\Test;

use Clicalmani\Routing\Exceptions\DuplicateRouteException;

/**
 * Cas limites du matching, déduits de la lecture de BasicBuilder / Route::equals().
 * Aucun n'a été exécuté : chaque test indique le résultat PRÉDIT avec le code actuel.
 */
class RoutingMatchingTest extends RoutingTestCase
{
    /**
     * PRÉDIT : ÉCHOUE (null).
     * parameters() fait un array_diff par valeur, sans tenir compte de la position :
     * la valeur 'orders' est trouvée dans ['orders', ':id'], donc aucun paramètre n'est
     * détecté et la route paramétrée est écartée dans locate().
     */
    public function test_parameter_value_equal_to_a_static_segment_name(): void
    {
        $this->router->get('/orders/:id', [DummyController::class, 'show']);
        
        $route = $this->matchRoute('/orders/orders');

        $this->assertNotNull($route);
        $this->assertSame('orders', $_REQUEST['id']);
    }

    /**
     * PRÉDIT : ÉCHOUE (la route est trouvée).
     * Route::equals() compare join('', ...) sans séparateur :
     * 'shift' + 'sanomalies' === 'shifts' + 'anomalies'.
     */
    public function test_segments_are_not_concatenated_without_separator(): void
    {
        $this->router->get('/shifts/anomalies', [DummyController::class, 'index']);

        $this->assertNull($this->matchRoute('/shift/sanomalies'));
    }

    /**
     * PRÉDIT : ÉCHOUE avec DuplicateRouteException.
     * Builder::isBuilt() compte les routes de même uri ('items/:id'), sans regarder les validateurs.
     * Si ce test passe, il existe un mécanisme que je n'ai pas vu.
     */
    public function test_same_uri_with_different_validators_is_not_a_duplicate(): void
    {
        $this->router->get('/items/:id', [DummyController::class, 'show'])->whereNumber('id');
        $this->router->get('/items/:id', [DummyController::class, 'index'])->whereIn('id', ['alpha', 'beta']);

        $this->assertSame('show', $this->matchRoute('/items/42')->action[1]);
        $this->assertSame('index', $this->matchRoute('/items/alpha')->action[1]);
        $this->assertNull($this->matchRoute('/items/gamma'));
    }

    /** Même uri, aucun validateur : doit rester un doublon (comportement actuel à figer). */
    public function test_same_uri_without_validators_is_a_duplicate(): void
    {
        $this->router->get('/items/:id', [DummyController::class, 'show']);
        $this->router->get('/items/:id', [DummyController::class, 'index']);

        $this->expectException(DuplicateRouteException::class);

        $this->matchRoute('/items/42');
    }
}