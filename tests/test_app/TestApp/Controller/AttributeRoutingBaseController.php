<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Routing\Attribute\Get;
use Cake\Routing\Attribute\Middleware;
use Cake\Routing\Attribute\Scope;

/**
 * Base controller fixture for inherited attribute-routing tests.
 */
#[Scope(path: '/base', namePrefix: 'base:')]
#[Middleware('auth')]
abstract class AttributeRoutingBaseController extends AppController
{
    /**
     * Parent action used to verify inherited method-level routes.
     *
     * @return void
     */
    #[Get('/parent', 'parent')]
    public function parentRoute(): void
    {
    }
}
