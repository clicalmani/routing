<?php
namespace Routing\Test;

use PHPUnit\Framework\Attributes\DataProvider;

class OptionalParametersTest extends RoutingTestCase
{
    /**
     * Règle attendue : les optionnels adjacents se retirent par la droite
     * (/a, /a/:x, /a/:x/:y — jamais /a/:y, ambigu avec /a/:x).
     * Des optionnels séparés par un segment statique se combinent librement.
     */
    public static function optionalProvider(): iterable
    {
        yield 'un optionnel final'      => ['/users/?:id', ['users', 'users/:id']];
        yield 'un optionnel au milieu'  => ['/users/?:user/posts', ['users/posts', 'users/:user/posts']];
        yield 'requis + optionnel'      => ['/a/:id/?:x', ['a/:id', 'a/:id/:x']];
        yield 'deux adjacents'          => ['/a/?:x/?:y', ['a', 'a/:x', 'a/:x/:y']];          // ÉCHOUE avant correctif
        yield 'trois adjacents'         => ['/a/?:x/?:y/?:z', ['a', 'a/:x', 'a/:x/:y', 'a/:x/:y/:z']]; // ÉCHOUE avant correctif
        yield 'deux séparés'            => ['/a/?:x/b/?:y', ['a/b', 'a/:x/b', 'a/b/:y', 'a/:x/b/:y']]; // ÉCHOUE avant correctif
    }

    #[DataProvider('optionalProvider')]
    public function test_optional_uris_are_expanded(string $declared, array $expected): void
    {
        $this->router->group(function () use ($declared) {
            $this->router->get($declared, [DummyController::class, 'index']);
        });

        $this->assertUris($expected);
    }

    public function test_non_optional_route_is_untouched(): void
    {
        $this->router->group(function () {
            $this->router->get('/users/:id', [DummyController::class, 'show']);
        });

        $this->assertUris(['users/:id']);
    }

    public function test_optional_route_outside_any_group(): void   // ÉCHOUE avant correctif ($old_group null)
    {
        $this->router->get('/users/?:id', [DummyController::class, 'index']);

        $this->assertUris(['users', 'users/:id']);
    }

    public function test_variants_are_registered_for_every_verb(): void
    {
        $this->router->group(function () {
            $this->router->post('/users/?:id', [DummyController::class, 'index']);
        });

        $this->assertUris(['users', 'users/:id'], 'post');
        $this->assertUris([], 'get');
    }

    public function test_group_receives_each_variant_exactly_once(): void
    {
        $group = $this->router->group(function () {
            $this->router->get('/users/?:id', [DummyController::class, 'index']);
        });

        $ids = array_map('spl_object_id', $group->routes);

        $this->assertCount(2, $ids);
        $this->assertCount(2, array_unique($ids));
    }

    public function test_prefix_is_applied_once_to_every_variant(): void
    {
        $this->router->group(['prefix' => 'admin'], function () {
            $this->router->get('/users/?:id', [DummyController::class, 'index']);
        });

        $this->assertUris(['admin/users', 'admin/users/:id']);
    }

    // --- Matching de bout en bout (BasicBuilder) -------------------------

    public function test_matches_with_and_without_optional_parameter(): void
    {
        $this->router->group(function () {
            $this->router->get('/users/?:id', [DummyController::class, 'show']);
        });

        $without = $this->matchRoute('/users');
        $this->assertNotNull($without);
        $this->assertSame('users', trim($without->uri, '/'));

        $with = $this->matchRoute('/users/42');
        $this->assertNotNull($with);
        $this->assertSame('users/:id', trim($with->uri, '/'));
        $this->assertSame('42', $_REQUEST['id']);
    }

    public function test_adjacent_optionals_bind_left_to_right(): void   // ÉCHOUE avant correctif
    {
        $this->router->group(function () {
            $this->router->get('/a/?:x/?:y', [DummyController::class, 'show']);
        });

        $this->assertNotNull($this->matchRoute('/a'));

        $this->assertNotNull($this->matchRoute('/a/7'));
        $this->assertSame('7', $_REQUEST['x']);
        $this->assertArrayNotHasKey('y', $_REQUEST);

        $this->assertNotNull($this->matchRoute('/a/7/8'));
        $this->assertSame(['x' => '7', 'y' => '8'], array_intersect_key($_REQUEST, ['x' => 1, 'y' => 1]));
    }

    public function test_validation_applies_to_optional_variants(): void
    {
        $this->router->group(function () {
            $this->router->get('/users/?:id', [DummyController::class, 'show'])->whereNumber('id');
        });

        $this->assertNotNull($this->matchRoute('/users'));
        $this->assertNotNull($this->matchRoute('/users/12'));
        $this->assertNull($this->matchRoute('/users/abc'));
    }

    public function test_no_duplicate_route_exception_when_matching(): void
    {
        $this->router->group(function () {
            $this->router->get('/users/?:id', [DummyController::class, 'show']);
        });

        // matches() lève DuplicateRouteException si une variante est enregistrée deux fois.
        $this->assertNotNull($this->matchRoute('/users'));
    }

    // --- Resources imbriquées (utilisent {?id}) --------------------------
    // Dépend de Str::singularize('users') === 'user' et ('posts') === 'post'.

    public function test_nested_resource_get_routes(): void
    {
        $this->router->resource('users.posts', DummyController::class);

        $this->assertUris([
            'users/posts',                    // index
            'users/:user/posts',              // index
            'users/posts/create',             // create
            'users/:user/posts/create',       // create
            'users/:user/posts/:post',        // show
            'users/:user/posts/:post/edit',   // edit
        ]);
    }

    public function test_nested_resource_store_routes(): void
    {
        $this->router->resource('users.posts', DummyController::class);

        $this->assertUris(['users/posts', 'users/:user/posts'], 'post');
    }
}