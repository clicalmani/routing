<?php
namespace Routing\Test;

use Clicalmani\Routing\Registry;
use Clicalmani\Routing\Route;
use Clicalmani\Routing\Validator;
use ReflectionProperty;

/**
 * Chaque test affirme ce que la DOCUMENTATION annonce.
 * Un échec = écart entre la doc et le code (corriger l'un ou l'autre).
 * Le résultat PRÉDIT par ma lecture du code est indiqué à chaque fois ; rien n'a été exécuté.
 */
class DocumentationClaimsTest extends RoutingTestCase
{
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

    // --- Resources -------------------------------------------------------

    /** Doc : "Singleton" → GET /profile, GET /profile/edit. PRÉDIT : ÉCHOUE (':profile', ':profile/edit'). */
    public function test_singleton_get_uris(): void
    {
        $this->router->singleton('profile', DummyController::class);

        $this->assertUris(['profile', 'profile/edit']);
    }

    /** La doc ne cite que PUT. PRÉDIT : PASSE (le code enregistre aussi PATCH) → à documenter. */
    public function test_resource_update_is_also_registered_as_patch(): void
    {
        $this->router->resource('users', DummyController::class);

        $this->assertContains('users/:user', $this->uris('patch'));
    }

    /** Doc : "->ignore()" désactive la vérification d'unicité. PRÉDIT : ÉCHOUE (le défaut du paramètre est false). */
    public function test_resource_ignore_without_argument_enables_the_flag(): void
    {
        $this->router->resource('users', DummyController::class)->ignore();

        $store = array_values(array_filter(
            Registry::getRoutesByVerb('post'),
            fn(Route $r) => is_array($r->action) && $r->action[1] === 'store'
        ));

        $this->assertNotEmpty($store);
        $this->assertTrue($store[0]->ignoreKeyWarning());
    }

    // --- Groupes imbriqués -------------------------------------------------

    /** Doc : groupes imbriqués. PRÉDIT : ÉCHOUE ('b/x' : le préfixe du parent n'atteint pas les routes du groupe interne). */
    public function test_nested_groups_accumulate_prefixes(): void
    {
        $this->router->group(['prefix' => 'a'], function () {
            $this->router->group(['prefix' => 'b'], function () {
                $this->router->get('/x', [DummyController::class, 'index']);
            });
        });

        $this->assertUris(['a/b/x']);
    }

    /** Doc : "Les middlewares d'un groupe parent sont hérités". PRÉDIT : ÉCHOUE. */
    public function test_nested_groups_inherit_parent_middleware(): void
    {
        $this->router->group(['middleware' => FakeBootMiddleware::class], function () {
            $this->router->controller(DummyController::class)->group(function () {
                $this->router->get('/download', 'index');
            })->prefix('members');
        });

        $this->assertContains(FakeBootMiddleware::class, $this->middlewaresOf('members/download'));
    }

    // --- Validation --------------------------------------------------------

    // --- API utilisée dans les exemples de la doc --------------------------

    /** Doc : ->scoped([...]) et ->withoutMiddleware(...) chaînés sur une route. PRÉDIT : ÉCHOUE (absents de Validator). */
    public function test_validator_exposes_the_methods_used_in_the_docs(): void
    {
        foreach (['scoped', 'withoutMiddleware'] as $method) {
            $this->assertTrue(
                method_exists(Validator::class, $method) || method_exists(Validator::class, '__call'),
                "Validator::$method() est utilisée dans la doc mais n'existe pas"
            );
        }
    }

    // --- Routes nommées ----------------------------------------------------

    /** Doc : "une exception est levée si un paramètre requis n'est pas fourni". PRÉDIT : ÉCHOUE (retourne '/users/'). */
    public function test_url_generation_throws_when_a_parameter_is_missing(): void
    {
        $this->router->get('/users/:id', [DummyController::class, 'show'])->name('users.show');

        $this->expectException(\Throwable::class);

        $this->router->resolve('users.show');
    }

    /** Un nom doit rester unique, aussi pour les routes optionnelles. PRÉDIT : ÉCHOUE (Group::name() ne vérifie rien). */
    public function test_duplicate_names_are_rejected_for_optional_routes(): void
    {
        $this->router->group(function () {
            $this->router->get('/a/?:x', [DummyController::class, 'index'])->name('dup');
        });

        $this->expectException(\Throwable::class);

        $this->router->group(function () {
            $this->router->get('/b/?:y', [DummyController::class, 'index'])->name('dup');
        });
    }
}