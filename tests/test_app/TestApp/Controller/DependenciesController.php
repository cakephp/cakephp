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

    public function requiredString(string $str)
    {
        return $this->response->withStringBody(json_encode(compact('str')));
    }

    public function optionalString(string $str = 'default val')
    {
        return $this->response->withStringBody(json_encode(compact('str')));
    }

    public function optionalDep($any = null, ?string $str = null, ?stdClass $dep = null)
    {
        return $this->response->withStringBody(json_encode(compact('dep', 'any', 'str')));
    }

    public function requiredDep(stdClass $dep, $any = null, ?string $str = null)
    {
        return $this->response->withStringBody(json_encode(compact('dep', 'any', 'str')));
    }

    public function variadic()
    {
        return $this->response->withStringBody(json_encode(['args' => func_get_args()]));
    }

    public function spread(string ...$args)
    {
        return $this->response->withStringBody(json_encode(['args' => $args]));
    }

    public function requiredParam($one)
    {
        return $this->response->withStringBody(json_encode(compact('one')));
    }
}
