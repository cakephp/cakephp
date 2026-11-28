<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Core\PluginInterface;
use Cake\Http\ServerRequest;

/**
 * Controller fixture with a trailing variadic parameter that must stay empty.
 */
class VariadicPluginsController extends Controller
{
    public readonly array $plugins;

    /**
     * @param \Cake\Http\ServerRequest $request The active request.
     * @param \Cake\Core\PluginInterface ...$plugins Must stay empty.
     */
    public function __construct(ServerRequest $request, PluginInterface ...$plugins)
    {
        parent::__construct($request);
        $this->plugins = $plugins;
    }
}
