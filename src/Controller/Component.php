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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventListenerInterface;
use Cake\Log\LogTrait;

/**
 * Base class for an individual Component. Components provide reusable bits of
 * controller logic that can be composed into a controller. Components also
 * provide request life-cycle callbacks for injecting logic at specific points.
 *
 * ### Initialize hook
 *
 * Like Controller and Table, this class has an initialize() hook that you can use
 * to add custom 'constructor' logic. It is important to remember that each request
 * (and sub-request) will only make one instance of any given component.
 *
 * ### Life cycle callbacks
 *
 * Components can provide several callbacks that are fired at various stages of the request
 * cycle. The available callbacks are:
 *
 * - `beforeFilter(EventInterface $event)`
 *   Called before the controller's beforeFilter method by default.
 * - `startup(EventInterface $event)`
 *   Called after the controller's beforeFilter method, and before the
 *   controller action is called.
 * - `beforeRender(EventInterface $event)`
 *   Called before the Controller beforeRender, and before the view class is loaded.
 * - `shutdown(EventInterface $event)`
 *   Called after the action is complete and the view has been rendered but
 *   before Controller::afterFilter().
 * - `beforeRedirect(EventInterface $event $url, Response $response)`
 *   Called before a redirect is done. Allows you to change the URL that will
 *   be redirected to by returning a Response instance with new URL set using
 *   Response::location(). Redirection can be prevented by stopping the event
 *   propagation.
 *
 * While the controller is not an explicit argument for the callback methods it
 * is the subject of each event and can be fetched using EventInterface::getSubject().
 *
 * @link https://book.cakephp.org/4/en/controllers/components.html
 * @see \Cake\Controller\Controller::$components
 */
class Component implements EventListenerInterface
{
    use InstanceConfigTrait;
    use LogTrait;

    /**
     * Component registry class used to lazy load components.
     *
     * @var \Cake\Controller\ComponentRegistry
     */
    protected $_registry;

    /**
     * Other Components this component uses.
     *
     * @var array
     */
    public $components = [];

    /**
     * Default config
     *
     * These are merged with user-provided config when the component is used.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * A component lookup table used to lazy load component objects.
     *
     * @var array
     */
    protected $_componentMap = [];

    /**
     * Constructor
     *
     * @param \Cake\Controller\ComponentRegistry $registry A component registry
     *  this component can use to lazy load its components.
     * @param array $config Array of configuration settings.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->_registry = $registry;

        $this->setConfig($config);

        if ($this->components) {
            $this->_componentMap = $registry->normalizeArray($this->components);
        }
        $this->initialize($config);
    }

    /**
     * Get the controller this component is bound to.
     *
     * @return \Cake\Controller\Controller The bound controller.
     */
    public function getController(): Controller
    {
        return $this->_registry->getController();
    }

    /**
     * Constructor hook method.
     *
     * Implement this method to avoid having to overwrite
     * the constructor and call parent.
     *
     * @param array $config The configuration settings provided to this component.
     * @return void
     */
    public function initialize(array $config): void
    {
    }

    /**
     * Magic method for lazy loading $components.
     *
     * @param string $name Name of component to get.
     * @return \Cake\Controller\Component|null A Component object or null.
     */
    public function __get(string $name)
    {
        if (isset($this->_componentMap[$name]) && !isset($this->{$name})) {
            $config = (array)$this->_componentMap[$name]['config'] + ['enabled' => false];
            $this->{$name} = $this->_registry->load($this->_componentMap[$name]['class'], $config);
        }
        if (!isset($this->{$name})) {
            return null;
        }

        return $this->{$name};
    }

    /**
     * Get the Controller callbacks this Component is interested in.
     *
     * Uses Conventions to map controller events to standard component
     * callback method names. By defining one of the callback methods a
     * component is assumed to be interested in the related event.
     *
     * Override this method if you need to add non-conventional event listeners.
     * Or if you want components to listen to non-standard events.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        $eventMap = [
            'Controller.initialize' => 'beforeFilter',
            'Controller.startup' => 'startup',
            'Controller.beforeRender' => 'beforeRender',
            'Controller.beforeRedirect' => 'beforeRedirect',
            'Controller.shutdown' => 'shutdown',
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
    public function __debugInfo(): array
    {
        return [
            'components' => $this->components,
            'implementedEvents' => $this->implementedEvents(),
            '_config' => $this->getConfig(),
        ];
    }
}
