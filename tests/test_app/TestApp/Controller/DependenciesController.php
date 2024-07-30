<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use stdClass;
use TestApp\ReflectionDependency;

/**
 * DependenciesController class
 */
class DependenciesController extends Controller
{
    public function __construct(
        ?ServerRequest $request = null,
        ?string $name = null,
        ?EventManagerInterface $eventManager = null,
        public ?stdClass $inject = null
    ) {
        parent::__construct($request, $name, $eventManager);
    }

    public function requiredString(string $str): Response
    {
        return $this->response->withStringBody(json_encode(['str' => $str]));
    }

    public function optionalString(string $str = 'default val'): Response
    {
        return $this->response->withStringBody(json_encode(['str' => $str]));
    }

    public function requiredTyped(float $one, int $two, bool $three, array $four): Response
    {
        return $this->response->withStringBody(json_encode(
            ['one' => $one, 'two' => $two, 'three' => $three, 'four' => $four],
            JSON_PRESERVE_ZERO_FRACTION
        ));
    }

    public function optionalTyped(float $one = 1.0, int $two = 2, bool $three = true): Response
    {
        return $this->response->withStringBody(json_encode(['one' => $one, 'two' => $two, 'three' => $three], JSON_PRESERVE_ZERO_FRACTION));
    }

    public function unsupportedTyped(iterable $one): Response
    {
        return $this->response->withStringBody(json_encode(['one' => $one]));
    }

    public function typedUnion(string|int $one): Response
    {
        return $this->response->withStringBody(json_encode(['one' => $one]));
    }

    public function optionalDep(mixed $any = null, ?string $str = null, ?stdClass $dep = null): Response
    {
        return $this->response->withStringBody(json_encode(['dep' => $dep, 'any' => $any, 'str' => $str]));
    }

    public function reflectionDep(ReflectionDependency $dep): Response
    {
        return $this->response->withStringBody(json_encode(['dep' => $dep]));
    }

    public function requiredDep(stdClass $dep, mixed $any = null, ?string $str = null): Response
    {
        return $this->response->withStringBody(json_encode(['dep' => $dep, 'any' => $any, 'str' => $str]));
    }

    public function variadic(): Response
    {
        return $this->response->withStringBody(json_encode(['args' => func_get_args()]));
    }

    public function spread(string ...$args): Response
    {
        return $this->response->withStringBody(json_encode(['args' => $args]));
    }

    public function requiredParam(mixed $one): Response
    {
        return $this->response->withStringBody(json_encode(['one' => $one]));
    }
}
