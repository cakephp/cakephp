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
 * UnionDependenciesController class
 *
 * Separate from Dependencies Controller because unions are not supported in PHP 7.4
 */
class UnionDependenciesController extends Controller
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

    public function typedUnion(string|int $one)
    {
        return $this->response->withStringBody(json_encode(compact('one')));
    }
}
