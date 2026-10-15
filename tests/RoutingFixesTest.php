<?php
namespace Routing\Test;

use Clicalmani\Routing\Factory\BasicBuilder;
use Clicalmani\Routing\Group;
use Clicalmani\Routing\Registry;
use Clicalmani\Routing\Resource;
use Clicalmani\Routing\Router;
use Clicalmani\Routing\Segment;
use ReflectionMethod;

/**
 * Les tests marqués "ÉCHOUE avant correctif" documentent le bug corrigé.
 * Non couvert ici : l'option 'middleware' de Router::group() (dépend de MiddlewareAccessor).
 */
class RoutingFixesTest extends RoutingTestCase
{
    // --- Router::group() ---------------------------------------------------

    public function test_group_without_middleware_key(): void          // ÉCHOUE avant correctif ($name indéfini)
    {
        $called = false;

        $group = $this->router->group(['foo' => 'bar'], function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame('', $group->getMiddleware());
        $this->assertNull(Registry::currentGroup());
    }

    public function test_group_where_applies_to_the_declared_parameter(): void   // ÉCHOUE avant correctif
    {
        $this->router->group(['prefix' => 'items', 'where' => ['id' => 'numeric']], function () {
            $this->router->get('/:id', [DummyController::class, 'show']);
        });

        $this->assertNotNull($this->matchRoute('/items/5'));
        $this->assertNull($this->matchRoute('/items/abc'));
    }

    public function test_group_where_handles_several_parameters(): void           // ÉCHOUE avant correctif
    {
        $this->router->group(['prefix' => 'items', 'where' => ['a' => 'numeric', 'b' => 'numeric']], function () {
            $this->router->get('/:a/:b', [DummyController::class, 'show']);
        });

        $this->assertNotNull($this->matchRoute('/items/1/2'));
        $this->assertNull($this->matchRoute('/items/1/x'));
        $this->assertNull($this->matchRoute('/items/x/2'));
    }

    // --- Group -------------------------------------------------------------

    public function test_group_where_accepts_an_array_of_parameters(): void       // ÉCHOUE avant correctif (TypeError)
    {
        $this->router->group(function () {
            $this->router->get('/x/?:a/?:b', [DummyController::class, 'show'])->whereNumber(['a', 'b']);
        });

        $this->assertNotNull($this->matchRoute('/x/1/2'));
        $this->assertNull($this->matchRoute('/x/1/z'));
    }

    public function test_get_middleware_without_middleware(): void                 // ÉCHOUE avant correctif (TypeError)
    {
        $this->assertSame('', (new Group)->getMiddleware());
    }

    // --- Prefix après une route optionnelle (cas réel : groupe "shifts") ---

    public function test_routes_after_an_optional_route_keep_the_group_prefix(): void
    {
        $this->router->controller(DummyController::class)->group(function () {
            $this->router->get('/checkin/?:date', 'index');
            $this->router->get('/settings', 'show');
        })->prefix('shifts');

        $this->assertUris(['shifts/checkin', 'shifts/checkin/:date', 'shifts/settings']);
    }

    public function test_prefixed_group_does_not_collide_with_a_root_route(): void
    {
        $this->router->get('/settings', [DummyController::class, 'show']);

        $this->router->controller(DummyController::class)->group(function () {
            $this->router->get('/checkin/?:date', 'index');
            $this->router->get('/settings', 'show');
        })->prefix('shifts');

        $this->assertUris(['settings', 'shifts/checkin', 'shifts/checkin/:date', 'shifts/settings']);
        $this->assertNotNull($this->matchRoute('/settings'));   // ne lève pas DuplicateRouteException
    }

    // --- Segment / Route ---------------------------------------------------

    public function test_segment_make_required_keeps_inner_question_marks(): void  // ÉCHOUE avant correctif
    {
        $segment = new Segment;
        $segment->name = '?:a?b';
        $segment->makeRequired();

        $this->assertSame(':a?b', $segment->name);
    }

    public function test_seems_optional_only_looks_at_segment_start(): void        // ÉCHOUE avant correctif
    {
        $builder = new BasicBuilder;

        $this->assertTrue($builder->create('/q/?:x')->seemsOptional());
        $this->assertFalse($builder->create('/q/x?:y')->seemsOptional());
    }

    // --- Resource / Router -------------------------------------------------

    public function test_resource_accepts_a_group_subclass(): void                 // ÉCHOUE avant correctif (get_class ===)
    {
        $group = new class extends Group {};
        $route = (new BasicBuilder)->create('/x');
        $group->addRoute($route);

        $resource = new Resource('users');
        $resource[] = $group;

        $this->assertSame('users', $route->main_resource);
    }

    public function test_resource_uri_collapses_repeated_slashes(): void           // ÉCHOUE avant correctif
    {
        $method = new ReflectionMethod(Router::class, '__parseResourceUri');

        $this->assertSame(
            '/users/create',
            $method->invoke($this->router, 'users', '{resource}/{?id}/{nested}/create')
        );
    }

    public function test_match_registers_every_verb_on_a_fresh_registry(): void    // ÉCHOUE avant correctif
    {
        $this->router->match(['get', 'post'], '/m', [DummyController::class, 'index']);

        $this->assertUris(['m'], 'get');
        $this->assertUris(['m'], 'post');
    }

    // --- Registry ----------------------------------------------------------

    public function test_registry_reset_clears_everything(): void                  // ÉCHOUE avant correctif (reset() absent)
    {
        $this->router->get('/x', [DummyController::class, 'index']);
        Registry::registerPattern('id', '\d+');

        Registry::reset();

        $this->assertSame([], Registry::getRoutes());
        $this->assertSame([], Registry::getGlobalPatterns());
        $this->assertNull(Registry::currentGroup());
    }

    public function test_remove_route_removes_the_exact_route(): void
    {
        $this->router->get('/users/:id', [DummyController::class, 'show']);
        $this->router->get('/users/42', [DummyController::class, 'index']);

        [$a, $b] = array_values(Registry::getRoutesByVerb('get'));
        $a->getSegments()[1]->value = '42';   // état laissé par un matching précédent

        $this->assertTrue(Registry::removeRoute($b, 'get'));
        $this->assertUris(['users/:id']);
    }

    public function test_static_route_wins_over_parameterized_route(): void
    {
        $this->router->get('/users/:id', [DummyController::class, 'show']);
        $this->router->get('/users/create', [DummyController::class, 'index']);

        $this->assertSame('users/create', trim($this->matchRoute('/users/create')->uri, '/'));
        $this->assertSame('users/:id', trim($this->matchRoute('/users/5')->uri, '/'));
    }

    public function test_same_shape_routes_are_told_apart_by_validation(): void
    {
        $this->router->get('/items/:id', [DummyController::class, 'show'])->whereNumber('id');
        $this->router->get('/items/:slug', [DummyController::class, 'index'])->whereIn('slug', ['alpha', 'beta']);

        $this->assertSame('items/:id', trim($this->matchRoute('/items/42')->uri, '/'));
        $this->assertSame('items/:slug', trim($this->matchRoute('/items/alpha')->uri, '/'));
        $this->assertNull($this->matchRoute('/items/gamma'));
    }

    public function test_named_route_of_another_verb_can_be_resolved(): void
    {
        $this->router->post('/auth', [DummyController::class, 'index'])->name('login');   // client = GET

        $this->assertSame('/auth', $this->router->resolve('login'));
    }
}