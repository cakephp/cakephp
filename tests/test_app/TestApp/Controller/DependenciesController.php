<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use stdClass;

/**
 * DependenciesController class
 */
class DependenciesController extends Controller
{
    public function __construct(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?string $name = null,
        ?EventManagerInterface $eventManager = null,
        ?ComponentRegistry $components = null,
        ?stdClass $inject = null
    ) {
        parent::__construct($request, $response, $name, $eventManager, $components);
        $this->inject = $inject;
    }

    /**
     * @return \Cake\Http\Response
     */
    public function requiredString(string $str)
    {
        return $this->response->withStringBody(json_encode(compact('str')));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function optionalString(string $str = 'default val')
    {
        return $this->response->withStringBody(json_encode(compact('str')));
    }

    public function requiredTyped(float $one, int $two, bool $three, array $four)
    {
        return $this->response->withStringBody(json_encode(
            compact('one', 'two', 'three', 'four'),
            JSON_PRESERVE_ZERO_FRACTION
        ));
    }

    public function optionalTyped(float $one = 1.0, int $two = 2, bool $three = true)
    {
        return $this->response->withStringBody(json_encode(compact('one', 'two', 'three'), JSON_PRESERVE_ZERO_FRACTION));
    }

    public function unsupportedTyped(iterable $one)
    {
        return $this->response->withStringBody(json_encode(compact('one')));
    }

    /**
     * @param mixed $any
     * @return \Cake\Http\Response
     */
    public function optionalDep($any = null, ?string $str = null, ?stdClass $dep = null)
    {
        return $this->response->withStringBody(json_encode(compact('dep', 'any', 'str')));
    }

    /**
     * @param \TestApp\ReflectionDependency $dep
     * @return \Cake\Http\Response
     */
    public function reflectionDep(\TestApp\ReflectionDependency $dep)
    {
        return $this->response->withStringBody(json_encode(compact('dep')));
    }

    /**
     * @param mixed $any
     * @return \Cake\Http\Response
     */
    public function requiredDep(stdClass $dep, $any = null, ?string $str = null)
    {
        return $this->response->withStringBody(json_encode(compact('dep', 'any', 'str')));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function variadic()
    {
        return $this->response->withStringBody(json_encode(['args' => func_get_args()]));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function spread(string ...$args)
    {
        return $this->response->withStringBody(json_encode(['args' => $args]));
    }

    /**
     * @param mixed $one
     * @return \Cake\Http\Response
     */
    public function requiredParam($one)
    {
        return $this->response->withStringBody(json_encode(compact('one')));
    }
}
