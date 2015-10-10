<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
 * - `beforeRender(Event $event, $viewFile)` - beforeRender is called before the view file is rendered.
 * - `afterRender(Event $event, $viewFile)` - afterRender is called after the view file is rendered
 *   but before the layout has been rendered.
 * - beforeLayout(Event $event, $layoutFile)` - beforeLayout is called before the layout is rendered.
 * - `afterLayout(Event $event, $layoutFile)` - afterLayout is called after the layout has rendered.
 * - `beforeRenderFile(Event $event, $viewFile)` - Called before any view fragment is rendered.
 * - `afterRenderFile(Event $event, $viewFile, $content)` - Called after any view fragment is rendered.
 *   If a listener returns a non-null value, the output of the rendered file will be set to that.
 *
 */
class Helper implements EventListenerInterface
{

    use InstanceConfigTrait;

    /**
     * List of helpers used by this helper
     *
     * @var array
     */
    public $helpers = [];

    /**
     * Default config for this helper.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * A helper lookup table used to lazy load helper objects.
     *
     * @var array
     */
    protected $_helperMap = [];

    /**
     * The current theme name if any.
     *
     * @var string
     */
    public $theme = null;

    /**
     * Request object
     *
     * @var \Cake\Network\Request
     */
    public $request = null;

    /**
     * Plugin path
     *
     * @var string
     */
    public $plugin = null;

    /**
     * Holds the fields ['field_name' => ['type' => 'string', 'length' => 100]],
     * primaryKey and validates ['field_name']
     *
     * @var array
     */
    public $fieldset = [];

    /**
     * Holds tag templates.
     *
     * @var array
     */
    public $tags = [];

    /**
     * The View instance this helper is attached to
     *
     * @var \Cake\View\View
     */
    protected $_View;

    /**
     * Default Constructor
     *
     * @param \Cake\View\View $View The View this helper is being attached to.
     * @param array $config Configuration settings for the helper.
     */
    public function __construct(View $View, array $config = [])
    {
        $this->_View = $View;
        $this->request = $View->request;

        $this->config($config);

        if (!empty($this->helpers)) {
            $this->_helperMap = $View->helpers()->normalizeArray($this->helpers);
        }
    }

    /**
     * Provide non fatal errors on missing method calls.
     *
     * @param string $method Method to invoke
     * @param array $params Array of params for the method.
     * @return void
     */
    public function __call($method, $params)
    {
        trigger_error(sprintf('Method %1$s::%2$s does not exist', get_class($this), $method), E_USER_WARNING);
    }

    /**
     * Lazy loads helpers.
     *
     * @param string $name Name of the property being accessed.
     * @return \Cake\View\Helper|null Helper instance if helper with provided name exists
     */
    public function __get($name)
    {
        if (isset($this->_helperMap[$name]) && !isset($this->{$name})) {
            $config = ['enabled' => false] + (array)$this->_helperMap[$name]['config'];
            $this->{$name} = $this->_View->loadHelper($this->_helperMap[$name]['class'], $config);
            return $this->{$name};
        }
    }

    /**
     * Returns a string to be used as onclick handler for confirm dialogs.
     *
     * @param string $message Message to be displayed
     * @param string $okCode Code to be executed after user chose 'OK'
     * @param string $cancelCode Code to be executed after user chose 'Cancel'
     * @param array $options Array of options
     * @return string onclick JS code
     */
    protected function _confirm($message, $okCode, $cancelCode = '', $options = [])
    {
        $message = json_encode($message);
        $confirm = "if (confirm({$message})) { {$okCode} } {$cancelCode}";
        if (isset($options['escape']) && $options['escape'] === false) {
            $confirm = h($confirm);
        }
        return $confirm;
    }

    /**
     * Adds the given class to the element options
     *
     * @param array $options Array options/attributes to add a class to
     * @param string $class The class name being added.
     * @param string $key the key to use for class.
     * @return array Array of options with $key set.
     */
    public function addClass(array $options = [], $class = null, $key = 'class')
    {
        if (isset($options[$key]) && trim($options[$key])) {
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
     * @return array
     */
    public function implementedEvents()
    {
        $eventMap = [
            'View.beforeRenderFile' => 'beforeRenderFile',
            'View.afterRenderFile' => 'afterRenderFile',
            'View.beforeRender' => 'beforeRender',
            'View.afterRender' => 'afterRender',
            'View.beforeLayout' => 'beforeLayout',
            'View.afterLayout' => 'afterLayout'
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
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'helpers' => $this->helpers,
            'theme' => $this->theme,
            'plugin' => $this->plugin,
            'fieldset' => $this->fieldset,
            'tags' => $this->tags,
            'implementedEvents' => $this->implementedEvents(),
            '_config' => $this->config(),
        ];
    }
}
