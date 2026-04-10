<?php
declare(strict_types=1);

namespace TestApp\Controller\Admin;

use Cake\Routing\Attribute\Get;
use TestApp\Controller\AppController;

/**
 * Admin-prefixed controller fixture for attribute-routing tests.
 */
class AttributeRoutingController extends AppController
{
    /**
     * Action fixture used to verify namespace-derived prefix routing.
     *
     * @return void
     */
    #[Get('/dashboard', 'admin-dashboard')]
    public function dashboard(): void
    {
    }
}
