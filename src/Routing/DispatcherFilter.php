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
 * @since         2.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing;

use Cake\Core\InstanceConfigTrait;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use InvalidArgumentException;

/**
 * This abstract class represents a filter to be applied to a dispatcher cycle. It acts as an
 * event listener with the ability to alter the request or response as needed before it is handled
 * by a controller or after the response body has already been built.
 *
 * Subclasses of this class use a class naming convention having a `Filter` suffix.
 *
 * ### Limiting filters to specific paths
 *
 * By using the `for` option you can limit with request paths a filter is applied to.
 * Both the before and after event will have the same conditions applied to them. For
 * example, if you only wanted a filter applied to blog requests you could do:
 *
 * ```
 * $filter = new BlogFilter(['for' => '/blog']);
 * ```
 *
 * When the above filter is connected to a dispatcher it will only fire
 * its `beforeDispatch` and `afterDispatch` methods on requests that start with `/blog`.
 *
 * The for condition can also be a regular expression by using the `preg:` prefix:
 *
 * ```
 * $filter = new BlogFilter(['for' => 'preg:#^/blog/\d+$#']);
 * ```
 *
 * ### Limiting filters based on conditions
 *
 * In addition to simple path based matching you can use a closure to match on arbitrary request
 * or response conditions. For example:
 *
 * ```
 * $cookieMonster = new CookieFilter([
 *   'when' => function ($req, $res) {
 *     // Custom code goes here.
 *   }
 * ]);
 * ```
 *
 * If your when condition returns `true` the before/after methods will be called.
 *
 * When using the `for` or `when` matchers, conditions will be re-checked on the before and after
 * callback as the conditions could change during the dispatch cycle.
 *
 */
class DispatcherFilter implements EventListenerInterface
{

    use InstanceConfigTrait;

    /**
     * Default priority for all methods in this filter
     *
     * @var int
     */
    protected $_priority = 10;

    /**
     * Default config
     *
     * These are merged with user-provided config when the class is used.
     * The when and for options allow you to define conditions that are checked before
     * your filter is called.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'when' => null,
        'for' => null,
        'priority' => null,
    ];

    /**
     * Constructor.
     *
     * @param array $config Settings for the filter.
     * @throws \InvalidArgumentException When 'when' conditions are not callable.
     */
    public function __construct($config = [])
    {
        if (!isset($config['priority'])) {
            $config['priority'] = $this->_priority;
        }
        $this->config($config);
        if (isset($config['when']) && !is_callable($config['when'])) {
            throw new InvalidArgumentException('"when" conditions must be a callable.');
        }
    }

    /**
     * Returns the list of events this filter listens to.
     * Dispatcher notifies 2 different events `Dispatcher.before` and `Dispatcher.after`.
     * By default this class will attach `preDispatch` and `postDispatch` method respectively.
     *
     * Override this method at will to only listen to the events you are interested in.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Dispatcher.beforeDispatch' => [
                'callable' => 'handle',
                'priority' => $this->_config['priority']
            ],
            'Dispatcher.afterDispatch' => [
                'callable' => 'handle',
                'priority' => $this->_config['priority']
            ],
        ];
    }

    /**
     * Handler method that applies conditions and resolves the correct method to call.
     *
     * @param \Cake\Event\Event $event The event instance.
     * @return mixed
     */
    public function handle(Event $event)
    {
        $name = $event->name();
        list(, $method) = explode('.', $name);
        if (empty($this->_config['for']) && empty($this->_config['when'])) {
            return $this->{$method}($event);
        }
        if ($this->matches($event)) {
            return $this->{$method}($event);
        }
    }

    /**
     * Check to see if the incoming request matches this filter's criteria.
     *
     * @param \Cake\Event\Event $event The event to match.
     * @return bool
     */
    public function matches(Event $event)
    {
        $request = $event->data['request'];
        $pass = true;
        if (!empty($this->_config['for'])) {
            $len = strlen('preg:');
            $for = $this->_config['for'];
            $url = $request->here(false);
            if (substr($for, 0, $len) === 'preg:') {
                $pass = (bool)preg_match(substr($for, $len), $url);
            } else {
                $pass = strpos($url, $for) === 0;
            }
        }
        if ($pass && !empty($this->_config['when'])) {
            $response = $event->data['response'];
            $pass = $this->_config['when']($request, $response);
        }

        return $pass;
    }

    /**
     * Method called before the controller is instantiated and called to serve a request.
     * If used with default priority, it will be called after the Router has parsed the
     * URL and set the routing params into the request object.
     *
     * If a Cake\Network\Response object instance is returned, it will be served at the end of the
     * event cycle, not calling any controller as a result. This will also have the effect of
     * not calling the after event in the dispatcher.
     *
     * If false is returned, the event will be stopped and no more listeners will be notified.
     * Alternatively you can call `$event->stopPropagation()` to achieve the same result.
     *
     * @param \Cake\Event\Event $event container object having the `request`, `response` and `additionalParams`
     *    keys in the data property.
     * @return void
     */
    public function beforeDispatch(Event $event)
    {
    }

    /**
     * Method called after the controller served a request and generated a response.
     * It is possible to alter the response object at this point as it is not sent to the
     * client yet.
     *
     * If false is returned, the event will be stopped and no more listeners will be notified.
     * Alternatively you can call `$event->stopPropagation()` to achieve the same result.
     *
     * @param \Cake\Event\Event $event container object having the `request` and  `response`
     *    keys in the data property.
     * @return void
     */
    public function afterDispatch(Event $event)
    {
    }
}
