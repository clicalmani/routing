<?php
namespace Routing\Test;

use Clicalmani\Routing\Factory\BasicBuilder;
use Clicalmani\Routing\Registry;
use Clicalmani\Routing\Route;
use Clicalmani\Routing\Router;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Prérequis (bootstrap PHPUnit) : la config doit fournir
 *   route.default_builder  = \Clicalmani\Routing\Factory\BasicBuilder::class
 *   route.parameter_prefix = ':'
 *
 * Hypothèse : client_url() lit $_SERVER['REQUEST_URI'] (adapter request() sinon).
 */
abstract class RoutingTestCase extends TestCase
{
    protected Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        Registry::reset();
        $this->router = new Router;
        $this->request('GET', '/');
    }

    protected function tearDown(): void
    {
        Registry::reset();
        $_REQUEST = [];
        parent::tearDown();
    }

    protected function request(string $verb, string $uri): void
    {
        $_SERVER['REQUEST_METHOD'] = strtoupper($verb);
        $_SERVER['REQUEST_URI']    = $uri;
        $_REQUEST = [];
    }

    /** URIs enregistrées pour un verbe, sans slash de bord, triées (les doublons sont conservés). */
    protected function uris(string $verb = 'get'): array
    {
        $uris = array_map(
            fn(Route $r) => trim($r->uri, '/'),
            array_values(Registry::getRoutesByVerb($verb))
        );
        sort($uris);

        return $uris;
    }

    protected function assertUris(array $expected, string $verb = 'get'): void
    {
        sort($expected);
        $this->assertSame($expected, $this->uris($verb));
    }

    protected function matchRoute(string $uri, string $verb = 'GET'): ?Route
    {
        $this->request($verb, $uri);

        return (new BasicBuilder)->getRoute();
    }
}

class DummyController
{
    public function index() {}
    public function show() {}
}