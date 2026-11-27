<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use TestApp\ReflectionDependency;

/**
 * Controller fixture whose request parameter is not the first one,
 * to verify the request is bound by type regardless of its position.
 */
class RequestNotFirstController extends Controller
{
    /**
     * @param \TestApp\ReflectionDependency $dependency Dependency resolved from the container.
     * @param \Cake\Http\ServerRequest $request The active request.
     */
    public function __construct(
        public readonly ReflectionDependency $dependency,
        ServerRequest $request,
    ) {
        parent::__construct($request);
    }
}
