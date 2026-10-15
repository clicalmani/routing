<?php
namespace Routing\Test;

use Clicalmani\Routing\Registry;

/**
 * Tests de Router::resolve() (génération d'URL par nom de route).
 * Écrits pour la nouvelle version ; la plupart échouent avec l'ancienne.
 */
class UrlGenerationTest extends RoutingTestCase
{
    private function named(string $uri, string $name): void
    {
        $this->router->group(function () use ($uri, $name) {
            $this->router->get($uri, [DummyController::class, 'show'])->name($name);
        });
    }

    // --- Paramètres requis --------------------------------------------------

    public function test_associative_parameters(): void
    {
        $this->named('/users/:id', 'users.show');

        $this->assertSame('/users/5', $this->router->resolve('users.show', ['id' => 5]));
    }

    public function test_positional_parameters(): void
    {
        $this->named('/users/:id/posts/:post', 'posts.show');

        $this->assertSame('/users/5/posts/9', $this->router->resolve('posts.show', 5, 9));
    }

    public function test_list_array_is_positional(): void
    {
        $this->named('/users/:id', 'users.show');

        $this->assertSame('/users/5', $this->router->resolve('users.show', [5]));
    }

    public function test_missing_required_parameter_throws(): void
    {
        $this->named('/users/:id', 'users.show');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('id');

        $this->router->resolve('users.show');
    }

    public function test_empty_value_counts_as_missing(): void
    {
        $this->named('/users/:id', 'users.show');

        $this->expectException(\InvalidArgumentException::class);

        $this->router->resolve('users.show', ['id' => '']);
    }

    public function test_values_are_url_encoded(): void
    {
        $this->named('/users/:id', 'users.show');

        $this->assertSame('/users/a%20b', $this->router->resolve('users.show', ['id' => 'a b']));
    }

    public function test_resolve_does_not_leak_state_into_the_registry(): void
    {
        $this->named('/users/:id', 'users.show');

        $this->assertSame('/users/5', $this->router->resolve('users.show', ['id' => 5]));
        $this->assertSame('/users/6', $this->router->resolve('users.show', ['id' => 6]));

        $route = Registry::getRoutesByVerb('get')[0];
        $this->assertNull($route->getSegments()[1]->value);
    }

    // --- Optionnels ---------------------------------------------------------

    public function test_optional_parameter_may_be_omitted(): void
    {
        $this->named('/checkin/?:date', 'checkin');

        $this->assertSame('/checkin', $this->router->resolve('checkin'));
        $this->assertSame('/checkin/2026-01-02', $this->router->resolve('checkin', ['date' => '2026-01-02']));
        $this->assertSame('/checkin', $this->router->resolve('checkin', ['date' => null]));
    }

    public function test_required_parameter_is_still_enforced_next_to_an_optional_one(): void
    {
        $this->named('/users/:id/posts/?:post', 'posts');

        $this->assertSame('/users/1/posts', $this->router->resolve('posts', ['id' => 1]));
        $this->assertSame('/users/1/posts/3', $this->router->resolve('posts', ['id' => 1, 'post' => 3]));
    }

    public function test_required_parameter_missing_with_only_the_optional_one_throws(): void
    {
        $this->named('/users/:id/posts/?:post', 'posts');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('id');

        $this->router->resolve('posts', ['post' => 3]);
    }

    public function test_adjacent_optionals_fill_left_to_right(): void
    {
        $this->named('/a/?:x/?:y', 'adj');

        $this->assertSame('/a', $this->router->resolve('adj'));
        $this->assertSame('/a/1', $this->router->resolve('adj', ['x' => 1]));
        $this->assertSame('/a/1/2', $this->router->resolve('adj', ['x' => 1, 'y' => 2]));
    }

    public function test_second_adjacent_optional_without_the_first_throws(): void
    {
        $this->named('/a/?:x/?:y', 'adj');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('y');

        $this->router->resolve('adj', ['y' => 2]);
    }

    public function test_separated_optionals_combine_freely(): void
    {
        $this->named('/a/?:x/b/?:y', 'sep');

        $this->assertSame('/a/b/2', $this->router->resolve('sep', ['y' => 2]));
        $this->assertSame('/a/1/b', $this->router->resolve('sep', ['x' => 1]));
        $this->assertSame('/a/1/b/2', $this->router->resolve('sep', ['x' => 1, 'y' => 2]));
    }

    // --- Cas particuliers ---------------------------------------------------

    public function test_unknown_name_returns_null(): void
    {
        $this->assertNull($this->router->resolve('does.not.exist'));
    }

    public function test_empty_name_does_not_match_unnamed_routes(): void
    {
        $this->router->get('/x', [DummyController::class, 'index']);

        $this->assertNull($this->router->resolve(''));
    }

    public function test_root_route_resolves_to_a_slash(): void
    {
        $this->router->get('/', [DummyController::class, 'index'])->name('home');

        $this->assertSame('/', $this->router->resolve('home'));
    }

    public function test_name_shared_across_verbs_resolves_once(): void
    {
        $this->router->match(['get', 'post'], '/m/:id', [DummyController::class, 'index'])->name('m');

        $this->assertSame('/m/1', $this->router->resolve('m', ['id' => 1]));
    }
}