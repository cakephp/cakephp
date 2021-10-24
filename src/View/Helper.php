<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventListenerInterface;

/**
 * Abstract base class for all other Helpers in CakePHP.
 * Provides common methods and features.
 *
 * ### Callback methods
 *
 * Helpers support a number of callback methods. These callbacks allow you to hook into
 * the various view lifecycle events and either modify existing view content or perform
 * other application specific logic. The events are not implemented by this base class, as
 * implementing a callback method subscribes a helper to the related event. The callback methods
 * are as follows:
 *
 * - `beforeRender(EventInterface $event, $viewFile)` - beforeRender is called before the view file is rendered.
 * - `afterRender(EventInterface $event, $viewFile)` - afterRender is called after the view file is rendered
 *   but before the layout has been rendered.
 * - beforeLayout(EventInterface $event, $layoutFile)` - beforeLayout is called before the layout is rendered.
 * - `afterLayout(EventInterface $event, $layoutFile)` - afterLayout is called after the layout has rendered.
 * - `beforeRenderFile(EventInterface $event, $viewFile)` - Called before any view fragment is rendered.
 * - `afterRenderFile(EventInterface $event, $viewFile, $content)` - Called after any view fragment is rendered.
 *   If a listener returns a non-null value, the output of the rendered file will be set to that.
 */
class Helper implements EventListenerInterface
{
    use InstanceConfigTrait;

    /**
     * List of helpers used by this helper
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Default config for this helper.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [];

    /**
     * A helper lookup table used to lazy load helper objects.
     *
     * @var array<string, array>
     */
    protected $_helperMap = [];

    /**
     * The View instance this helper is attached to
     *
     * @var \Cake\View\View
     */
    protected $_View;

    /**
     * Default Constructor
     *
     * @param \Cake\View\View $view The View this helper is being attached to.
     * @param array<string, mixed> $config Configuration settings for the helper.
     */
    public function __construct(View $view, array $config = [])
    {
        $this->_View = $view;
        $this->setConfig($config);

        if (!empty($this->helpers)) {
            $this->_helperMap = $view->helpers()->normalizeArray($this->helpers);
        }

        $this->initialize($config);
    }

    /**
     * Provide non fatal errors on missing method calls.
     *
     * @param string $method Method to invoke
     * @param array $params Array of params for the method.
     * @return mixed|void
     */
    public function __call(string $method, array $params)
    {
        trigger_error(sprintf('Method %1$s::%2$s does not exist', static::class, $method), E_USER_WARNING);
    }

    /**
     * Lazy loads helpers.
     *
     * @param string $name Name of the property being accessed.
     * @return \Cake\View\Helper|null|void Helper instance if helper with provided name exists
     */
    public function __get(string $name)
    {
        if (isset($this->_helperMap[$name]) && !isset($this->{$name})) {
            $config = ['enabled' => false] + (array)$this->_helperMap[$name]['config'];
            $this->{$name} = $this->_View->loadHelper($this->_helperMap[$name]['class'], $config);

            return $this->{$name};
        }
    }

    /**
     * Get the view instance this helper is bound to.
     *
     * @return \Cake\View\View The bound view instance.
     */
    public function getView(): View
    {
        return $this->_View;
    }

    /**
     * Returns a string to be used as onclick handler for confirm dialogs.
     *
     * @param string $okCode Code to be executed after user chose 'OK'
     * @param string $cancelCode Code to be executed after user chose 'Cancel'
     * @return string "onclick" JS code
     */
    protected function _confirm(string $okCode, string $cancelCode): string
    {
        return "if (confirm(this.dataset.confirmMessage)) { {$okCode} } {$cancelCode}";
    }

    /**
     * Adds the given class to the element options
     *
     * @param array<string, mixed> $options Array options/attributes to add a class to
     * @param string $class The class name being added.
     * @param string $key the key to use for class. Defaults to `'class'`.
     * @return array<string, mixed> Array of options with $key set.
     */
    public function addClass(array $options, string $class, string $key = 'class'): array
    {
        if (isset($options[$key]) && is_array($options[$key])) {
            $options[$key][] = $class;
        } elseif (isset($options[$key]) && trim($options[$key])) {
            $options[$key] .= ' ' . $class;
        } else {
            $options[$key] = $class;
        }

        return $options;
    }

    /**
     * Get the View callbacks this helper is interested in.
     *
     * By defining one of the callback methods a helper is assumed
     * to be interested in the related event.
     *
     * Override this method if you need to add non-conventional event listeners.
     * Or if you want helpers to listen to non-standard events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        $eventMap = [
            'View.beforeRenderFile' => 'beforeRenderFile',
            'View.afterRenderFile' => 'afterRenderFile',
            'View.beforeRender' => 'beforeRender',
            'View.afterRender' => 'afterRender',
            'View.beforeLayout' => 'beforeLayout',
            'View.afterLayout' => 'afterLayout',
        ];
        $events = [];
        foreach ($eventMap as $event => $method) {
            if (method_exists($this, $method)) {
                $events[$event] = $method;
            }
        }

        return $events;
    }

    /**
     * Constructor hook method.
     *
     * Implement this method to avoid having to overwrite the constructor and call parent.
     *
     * @param array<string, mixed> $config The configuration settings provided to this helper.
     * @return void
     */
    public function initialize(array $config): void
    {
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'helpers' => $this->helpers,
            'implementedEvents' => $this->implementedEvents(),
            '_config' => $this->getConfig(),
        ];
    }
}
