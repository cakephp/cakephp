<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use TestApp\ReflectionDependency;

/**
 * Controller fixture used to test that ControllerFactory auto-wires
 * required constructor dependencies for controllers that are not
 * explicitly registered in the container.
 */
class ConstructorDependenciesController extends Controller
{
    /**
     * Constructor.
     *
     * `$serverRequest` is intentionally not named `$request` to verify that
     * the factory binds the active request regardless of the parameter name.
     *
     * @param \Cake\Http\ServerRequest $serverRequest The active request.
     * @param \TestApp\ReflectionDependency $dependency Dependency resolved from the container.
     * @param \Cake\Controller\ComponentRegistry|null $components Component registry.
     */
    public function __construct(
        // Intentionally not named $request: the factory must resolve the request by type.
        ServerRequest $serverRequest,
        public readonly ReflectionDependency $dependency,
        ?ComponentRegistry $components = null,
    ) {
        parent::__construct($serverRequest, components: $components);
    }
}
