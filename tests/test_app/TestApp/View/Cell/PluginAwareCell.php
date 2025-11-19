<?php
declare(strict_types=1);

namespace TestApp\View\Cell;

use Cake\View\Cell;

/**
 * PluginAwareCell class for testing plugin access during initialize.
 */
class PluginAwareCell extends Cell
{
    /**
     * Plugin name captured during initialize.
     *
     * @var string|null
     */
    public ?string $pluginFromInitialize = null;

    /**
     * Plugin name captured during action.
     *
     * @var string|null
     */
    public ?string $pluginFromAction = null;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->pluginFromInitialize = $this->viewBuilder()->getPlugin();
    }

    /**
     * Default cell action.
     */
    public function display(): void
    {
        $this->pluginFromAction = $this->viewBuilder()->getPlugin();
        $this->set('pluginFromInitialize', $this->pluginFromInitialize);
        $this->set('pluginFromAction', $this->pluginFromAction);
    }
}
