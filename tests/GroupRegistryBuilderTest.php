<?php
namespace Routing\Test;

use Clicalmani\Routing\Factory\BasicBuilder;
use Clicalmani\Routing\Factory\LaravelStyleBuilder;
use Clicalmani\Routing\Group;
use Clicalmani\Routing\Record;
use Clicalmani\Routing\Registry;
use Clicalmani\Routing\Segment;

class GroupRegistryBuilderTest extends RoutingTestCase
{
    // --- Pile de groupes -------------------------------------------------

    public function test_current_group_is_cleared_after_group(): void   // ÉCHOUE avant correctif
    {
        $this->router->group(fn() => null);

        $this->assertNull(Registry::currentGroup());
    }

    public function test_nested_group_restores_its_parent(): void       // ÉCHOUE avant correctif
    {
        $seen = [];

        $this->router->group(function () use (&$seen) {
            $seen['outer'] = Registry::currentGroup();
            $this->router->group(fn() => null);
            $seen['after_inner'] = Registry::currentGroup();
        });

        $this->assertInstanceOf(Group::class, $seen['outer']);
        $this->assertSame($seen['outer'], $seen['after_inner']);
        $this->assertNull(Registry::currentGroup());
    }

    public function test_optional_route_restores_group_stack(): void
    {
        $inside = null;

        $this->router->group(function () use (&$inside) {
            $outer = Registry::currentGroup();
            $this->router->get('/users/?:id', [DummyController::class, 'index']);
            $inside = (Registry::currentGroup() === $outer);
        });

        $this->assertTrue($inside);
        $this->assertNull(Registry::currentGroup());
    }

    public function test_group_add_route_is_idempotent(): void          // ÉCHOUE avant correctif
    {
        $group = new Group;
        $route = (new BasicBuilder)->create('/x');

        $group->addRoute($route);
        $group->addRoute($route);

        $this->assertCount(1, $group->routes);
    }

    // --- Recording -------------------------------------------------------

    public function test_record_does_not_duplicate_routes(): void       // ÉCHOUE avant correctif
    {
        $route = (new BasicBuilder)->create('/x');

        Record::start('m');
        Record::add($route);
        Record::stop();

        $this->assertCount(1, Record::get()['m']);
    }

    // --- Segment / Route -------------------------------------------------

    public function test_segment_make_required_only_strips_leading_question_mark(): void
    {
        $segment = new Segment;
        $segment->name = '?:id';
        $segment->makeRequired();

        $this->assertSame(':id', $segment->name);
    }

    public function test_route_seems_optional(): void
    {
        $builder = new BasicBuilder;

        $this->assertTrue($builder->create('/users/?:id')->seemsOptional());
        $this->assertFalse($builder->create('/users/:id')->seemsOptional());
    }

    // --- LaravelStyleBuilder ---------------------------------------------

    public function test_laravel_builder_converts_syntax(): void        // ÉCHOUE avant correctif (fatal : getClientRoute private)
    {
        $route = (new LaravelStyleBuilder)->create('/users/{id}/posts/{post?}');

        $this->assertSame(['users', ':id', 'posts', '?:post'], $route->getSegmentsNames());
    }

    public function test_laravel_builder_does_not_flag_every_route_as_duplicate(): void   // ÉCHOUE avant correctif
    {
        $this->router->get('/users', [DummyController::class, 'index']);
        $this->request('GET', '/users');

        $this->assertCount(1, (new LaravelStyleBuilder)->matches('get'));
    }

    public function test_group_callback_runs_exactly_once(): void
    {
        $calls = 0;

        $this->router->group(function () use (&$calls) { $calls++; });
        $this->router->group(['prefix' => 'x'], function () use (&$calls) { $calls++; });

        $this->assertSame(2, $calls);
    }
}