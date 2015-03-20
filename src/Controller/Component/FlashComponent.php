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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Network\Exception\InternalErrorException;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * The CakePHP FlashComponent provides a way for you to write a flash variable
 * to the session from your controllers, to be rendered in a view with the
 * FlashHelper.
 */
class FlashComponent extends Component
{

    /**
     * The Session object instance
     *
     * @var \Cake\Network\Session
     */
    protected $_session;

    /**
     * Default configuration
     *
     * @var array
     */
    protected $_defaultConfig = [
        'key' => 'flash',
        'element' => 'default',
        'params' => [],
        'reset' => false,
        'stackLimit' => 50
    ];

    /**
     * Constructor
     *
     * @param ComponentRegistry $registry A ComponentRegistry for this component
     * @param array $config Array of config.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->_session = $registry->getController()->request->session();
    }

    /**
     * Used to set a session variable that can be used to output messages in the view.
     *
     * In your controller: $this->Flash->set('This has been saved');
     *
     * ### Options:
     *
     * - `key` The key to set under the session's Flash key
     * - `element` The element used to render the flash message. Default to 'default'.
     * - `params` An array of variables to make available when using an element
     * - `reset` A bool. If true, this will destroy the current stack and start a
     *  new one with $message
     *
     * @param string|\Exception $message Message to be flashed. If an instance
     *   of \Exception the exception message will be used and code will be set
     *   in params.
     * @param array $options An array of options
     * @return int The last inserted index to the stack
     */
    public function set($message, array $options = [])
    {
        $options += $this->config();

        if ($message instanceof \Exception) {
            $options['params'] += ['code' => $message->getCode()];
            $message = $message->getMessage();
        }

        $options['element'] = $this->_getElement($options['element']);

        $sessionKey = 'Flash.' . $options['key'];
        $messages = [];
        $index = 0;

        if ($options['reset'] === false) {
            $messages = $this->_session->read($sessionKey);
        }

        if (!empty($messages)) {
            end($messages);
            $index = key($messages);
            reset($messages);
            $index++;
        }

        if ($index >= $this->config('stackLimit')) {
            array_shift($messages);
            $this->_session->write($sessionKey, $messages);
            $index--;
        }

        $sessionKey .= '.' . $index;
        $this->_session->write($sessionKey, [
            'message' => $message,
            'key' => $options['key'],
            'element' => $options['element'],
            'params' => $options['params']
        ]);

        return $index;
    }

    /**
     * Edit a message of the messages stack
     *
     * @param int $index The index of the message to edit
     * @param string|array $message Message as a string to overwrite or an array of params
     *   Available keys are :
     *   - `element` The element used to render the flash message. The format is the same as
     *   expected for the $options value of the FlashComponent::set() method
     *   - `message` The message to overwrite
     *   - `params` An array of variables to make available when using an element
     * @return void
     */
    public function edit($index, $message, $key = 'flash')
    {
        $sessionKey = 'Flash.' . $key . '.' . $index;
        if ($this->_session->check($sessionKey) === false) {
            return;
        }

        $session = $this->_session->read($sessionKey);
        if (is_string($message)) {
            $session['message'] = $message;
        } elseif (is_array($message)) {
            if (!empty($message['element'])) {
                $message['element'] = $this->_getElement($message['element']);
            }
            $session = Hash::merge($session, $message);
        }

        $this->_session->write($sessionKey, $session);
    }

    /**
     * Delete a message from the session
     * If there are no remaining messages (in case of a stack), the stack
     * array is nulled
     *
     * @param string $key The flash key where the message is stored
     * @param null|string $index The index of the message to delete
     * @return void
     */
    public function delete($key = '', $index = null)
    {
        if (empty($key)) {
            $key = $this->config('key');
        }

        $sessionKey = $noIndexKey = 'Flash.' . $key;
        if ($index !== null) {
            $sessionKey .= '.' . $index;
        }

        if ($this->_session->check($sessionKey)) {
            $this->_session->delete($sessionKey);
        }

        $remaining = $this->_session->read($noIndexKey);
        if (is_array($remaining) && empty($remaining)) {
            $this->_session->delete($noIndexKey);
        }
    }

    /**
     * Delete all messages from a special type
     *
     * @param string $type The type of message to clear
     * @param string $key The flash key where the message is stored
     * @return void
     */
    public function clear($type, $key = '')
    {
        if (empty($key)) {
            $key = $this->config('key');
        }

        $messages = $this->_session->read('Flash.' . $key);
        if (!is_numeric(key($messages)) && $this->_hasType($messages, $type)) {
            $this->delete($key);
        } else {
            foreach ($messages as $index => $message) {
                if ($this->_hasType($message, $type)) {
                    $this->delete($key, $index);
                }
            }
        }
    }

    /**
     * Check if the given $message is of type $type
     *
     * @param array $message Flash message array to test the type of
     * @param string $type Type of message to test against
     * @return bool
     */
    protected function _hasType(array $message, $type)
    {
        $elementParts = explode('/', $message['element']);
        $messageType = end($elementParts);
        return $messageType === $type;
    }

    /**
     * Get the correct and full element path for the given $optElement
     * $optElement is supposed to be of the same type as the one given to the
     * FlashComponent::set() method
     *
     * @param string $optElement Element path to resolve
     * @return string
     */
    protected function _getElement($optElement)
    {
        list($plugin, $element) = pluginSplit($optElement);

        if ($plugin) {
            $optElement = $plugin . '.Flash/' . $element;
        } else {
            $optElement = 'Flash/' . $element;
        }

        return $optElement;
    }

    /**
     * Magic method for verbose flash methods based on element names.
     *
     * For example: $this->Flash->success('My message') would use the
     * success.ctp element under `src/Template/Element/Flash` for rendering the
     * flash message.
     *
     * Note that the parameter `element` will be always overridden. In order to call a
     * specific element from a plugin, you should set the `plugin` option in $args.
     *
     * For example: `$this->Flash->warning('My message', ['plugin' => 'PluginName'])` would
     * use the warning.ctp element under `plugins/PluginName/src/Template/Element/Flash` for
     * rendering the flash message.
     *
     * @param string $name Element name to use.
     * @param array $args Parameters to pass when calling `FlashComponent::set()`.
     * @return null|int If stacking is disabled, will return null. Otherwise, it will return the
     * last inserted index to the stack
     * @throws \Cake\Network\Exception\InternalErrorException If missing the flash message.
     */
    public function __call($name, $args)
    {
        $element = Inflector::underscore($name);

        if (count($args) < 1) {
            throw new InternalErrorException('Flash message missing.');
        }

        $options = ['element' => $element];

        if (!empty($args[1])) {
            if (!empty($args[1]['plugin'])) {
                $options = ['element' => $args[1]['plugin'] . '.' . $element];
                unset($args[1]['plugin']);
            }
            $options += (array)$args[1];
        }

        return $this->set($args[0], $options);
    }
}
