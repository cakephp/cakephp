<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use TestApp\ReflectionDependency;

class ConstructorDependenciesController extends Controller
{
    public function __construct(
        // Intentionally not named $request: the factory must resolve the request by type.
        ServerRequest $serverRequest,
        public readonly ReflectionDependency $dependency,
        ?ComponentRegistry $components = null,
    ) {
        parent::__construct($serverRequest, components: $components);
    }
}
