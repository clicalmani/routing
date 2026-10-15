<?php
namespace Routing\Test;

use Clicalmani\Core\Http\Middlewares\Middleware;
use Clicalmani\Core\Http\Middlewares\MiddlewareAccessor;
use Clicalmani\Core\Http\RequestInterface;
use Clicalmani\Core\Http\ResponseInterface;
use Clicalmani\Routing\Exceptions\MiddlewareNotFoundException;
use Clicalmani\Routing\Registry;
use Clicalmani\Routing\Route;
use Clicalmani\Routing\Router;
use ReflectionProperty;

/**
 * Middleware factice calqué sur App\Http\Middlewares\Authenticator :
 * boot() enregistre des routes, comme $this->include('auth').
 *
 * MiddlewareAccessor::getMiddleware() résout un nom de classe complet
 * directement (isInline), donc FakeBootMiddleware::class sert de nom.
 */
class FakeBootMiddleware extends Middleware
{
    public function handle(RequestInterface $request, ResponseInterface $response, \Closure $next) : \Clicalmani\Core\Http\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface
    {
        return $next();
    }

    public function boot() : void
    {
        (new Router)->get('/secret', [DummyController::class, 'index']);
    }
}

class RouterMiddlewareTest extends RoutingTestCase
{
    private const NAME = FakeBootMiddleware::class;

    /** @return string[] */
    private function middlewaresOf(string $uri, string $verb = 'get'): array
    {
        foreach (Registry::getRoutesByVerb($verb) as $route) {
            if (trim($route->uri, '/') === $uri) {
                return (new ReflectionProperty(Route::class, 'middlewares'))->getValue($route);
            }
        }

        $this->fail("Route introuvable : $uri");
    }

    // --- Résolution ---------------------------------------------------------

    public function test_unknown_middleware_throws(): void
    {
        $this->expectException(MiddlewareNotFoundException::class);

        $this->router->middleware('does-not-exist');
    }

    public function test_group_with_unknown_middleware_throws(): void
    {
        $this->expectException(MiddlewareNotFoundException::class);

        $this->router->group(['middleware' => 'does-not-exist'], fn() => null);
    }

    public function test_class_name_resolves_to_an_instance(): void
    {
        $this->assertInstanceOf(FakeBootMiddleware::class, MiddlewareAccessor::getMiddleware(self::NAME));
    }

    // --- Router::middleware() ----------------------------------------------

    public function test_routes_registered_by_boot_receive_the_middleware_name(): void
    {
        $this->router->middleware(self::NAME);

        $this->assertContains(self::NAME, $this->middlewaresOf('secret'));
    }

    public function test_sub_middlewares_are_added_with_the_main_one(): void
    {
        $this->router->middleware(self::NAME . '@admin');

        $middlewares = $this->middlewaresOf('secret');
        $this->assertContains(self::NAME, $middlewares);
        $this->assertContains('admin', $middlewares);
    }

    // --- Router::group(['middleware' => ...]) ------------------------------

    public function test_group_middleware_applies_to_inner_routes(): void
    {
        $this->router->group(['middleware' => self::NAME], function () {
            $this->router->get('/inner', [DummyController::class, 'index']);
        });

        $this->assertContains(self::NAME, $this->middlewaresOf('inner'));
    }

    public function test_group_middleware_is_not_ignored_with_a_prefix(): void     // ÉCHOUE avant correctif
    {
        $this->router->group(['prefix' => 'p', 'middleware' => self::NAME], function () {
            $this->router->get('/inner', [DummyController::class, 'index']);
        });

        $this->assertContains(self::NAME, $this->middlewaresOf('p/inner'));
    }

    public function test_group_middleware_does_not_leak_to_later_routes(): void     // ÉCHOUE avant correctif (pile)
    {
        $this->router->group(['middleware' => self::NAME], fn() => null);
        $this->router->get('/outside', [DummyController::class, 'index']);

        $this->assertNull(Registry::currentGroup());
        $this->assertNotContains(self::NAME, $this->middlewaresOf('outside'));
    }

    public function test_pipe_separated_names_are_all_applied(): void
    {
        $this->router->group(['middleware' => self::NAME . '|' . self::NAME . '@admin'], function () {
            $this->router->get('/inner', [DummyController::class, 'index']);
        });

        $this->assertContains(self::NAME, $this->middlewaresOf('inner'));
    }

    // --- MiddlewareAccessor ------------------------------------------------

    public function test_accessor_params_are_empty_without_arguments(): void        // ÉCHOUE avant correctif (['' ])
    {
        $this->assertSame([], MiddlewareAccessor::fromName('auth')->params());
    }

    public function test_accessor_parses_name_and_params(): void
    {
        $accessor = MiddlewareAccessor::fromName('role:admin,user');

        $this->assertSame('role', $accessor->name());
        $this->assertSame(['admin', 'user'], $accessor->params());
    }

    public function test_accessors_do_not_share_state(): void                       // ÉCHOUE avant correctif ($pairs static)
    {
        $first  = MiddlewareAccessor::fromName('a:1');
        $second = MiddlewareAccessor::fromName('b:2');

        $this->assertSame('a', $first->name());
        $this->assertSame(['1'], $first->params());
        $this->assertSame('b', $second->name());
    }
}