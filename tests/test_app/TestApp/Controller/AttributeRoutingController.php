<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Routing\Attribute\Extensions;
use Cake\Routing\Attribute\Get;
use Cake\Routing\Attribute\Post;
use Cake\Routing\Attribute\Route;
use Cake\Routing\Attribute\Scope;

/**
 * Controller fixture that defines method-level attribute routes.
 */
#[Scope(path: '/attr', namePrefix: 'attr:')]
#[Extensions(['json'])]
class AttributeRoutingController extends AttributeRoutingBaseController
{
    /**
     * Action fixture used to verify GET and POST attribute shortcuts.
     *
     * @return void
     */
    #[Get('/index', 'index')]
    #[Post('/index', 'index-post')]
    public function index(): void
    {
    }

    /**
     * Action fixture used to verify placeholder patterns and pass parameters.
     *
     * @param int $id Route parameter value.
     * @return void
     */
    #[Route('/view/{id}', name: 'view', patterns: ['id' => '\\d+'], pass: ['id'])]
    public function view(int $id): void
    {
    }

    /**
     * Action fixture used to verify method-level extension overrides.
     *
     * @return void
     */
    #[Route('/feed', name: 'feed')]
    #[Extensions(['xml'])]
    public function feed(): void
    {
    }

    /**
     * Action fixture used to verify inferred pass parameter ordering.
     *
     * @param string $slug Route slug value.
     * @param int $id Route identifier value.
     * @return void
     */
    public function reorder(string $slug, int $id): void
    {
    }
}
