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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\FlashMessage;
use Cake\Utility\Inflector;
use Throwable;

/**
 * The CakePHP FlashComponent provides a way for you to write a flash variable
 * to the session from your controllers, to be rendered in a view with the
 * FlashHelper.
 *
 * @method void success(string $message, array $options = []) Set a message using "success" element
 * @method void info(string $message, array $options = []) Set a message using "info" element
 * @method void warning(string $message, array $options = []) Set a message using "warning" element
 * @method void error(string $message, array $options = []) Set a message using "error" element
 */
class FlashComponent extends Component
{
    /**
     * Default configuration
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'key' => 'flash',
        'element' => 'default',
        'params' => [],
        'clear' => false,
        'duplicate' => true,
    ];

    /**
     * Used to set a session variable that can be used to output messages in the view.
     * If you make consecutive calls to this method, the messages will stack (if they are
     * set with the same flash key)
     *
     * In your controller: $this->Flash->set('This has been saved');
     *
     * ### Options:
     *
     * - `key` The key to set under the session's Flash key
     * - `element` The element used to render the flash message. Default to 'default'.
     * - `params` An array of variables to make available when using an element
     * - `clear` A bool stating if the current stack should be cleared to start a new one
     * - `escape` Set to false to allow templates to print out HTML content
     *
     * @param \Throwable|string $message Message to be flashed. If an instance
     *   of \Throwable the throwable message will be used and code will be set
     *   in params.
     * @param array<string, mixed> $options An array of options
     * @return void
     */
    public function set($message, array $options = []): void
    {
        if ($message instanceof Throwable) {
            $this->flash()->setExceptionMessage($message, $options);
        } else {
            $this->flash()->set($message, $options);
        }
    }

    /**
     * Get flash message utility instance.
     *
     * @return \Cake\Http\FlashMessage
     */
    protected function flash(): FlashMessage
    {
        return $this->getController()->getRequest()->getFlash();
    }

    /**
     * Proxy method to FlashMessage instance.
     *
     * @param array<string, mixed>|string $key The key to set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @param bool $merge Whether to recursively merge or overwrite existing config, defaults to true.
     * @return $this
     * @throws \Cake\Core\Exception\CakeException When trying to set a key that is invalid.
     */
    public function setConfig($key, $value = null, $merge = true)
    {
        $this->flash()->setConfig($key, $value, $merge);

        return $this;
    }

    /**
     * Proxy method to FlashMessage instance.
     *
     * @param string|null $key The key to get or null for the whole config.
     * @param mixed $default The return value when the key does not exist.
     * @return mixed Configuration data at the named key or null if the key does not exist.
     */
    public function getConfig(?string $key = null, $default = null)
    {
        return $this->flash()->getConfig($key, $default);
    }

    /**
     * Proxy method to FlashMessage instance.
     *
     * @param string $key The key to get.
     * @return mixed Configuration data at the named key
     * @throws \InvalidArgumentException
     */
    public function getConfigOrFail(string $key)
    {
        return $this->flash()->getConfigOrFail($key);
    }

    /**
     * Proxy method to FlashMessage instance.
     *
     * @param array<string, mixed>|string $key The key to set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @return $this
     */
    public function configShallow($key, $value = null)
    {
        $this->flash()->configShallow($key, $value);

        return $this;
    }

    /**
     * Magic method for verbose flash methods based on element names.
     *
     * For example: $this->Flash->success('My message') would use the
     * `success.php` element under `templates/element/flash/` for rendering the
     * flash message.
     *
     * If you make consecutive calls to this method, the messages will stack (if they are
     * set with the same flash key)
     *
     * Note that the parameter `element` will be always overridden. In order to call a
     * specific element from a plugin, you should set the `plugin` option in $args.
     *
     * For example: `$this->Flash->warning('My message', ['plugin' => 'PluginName'])` would
     * use the `warning.php` element under `plugins/PluginName/templates/element/flash/` for
     * rendering the flash message.
     *
     * @param string $name Element name to use.
     * @param array $args Parameters to pass when calling `FlashComponent::set()`.
     * @return void
     * @throws \Cake\Http\Exception\InternalErrorException If missing the flash message.
     */
    public function __call(string $name, array $args)
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

        $this->set($args[0], $options);
    }
}
