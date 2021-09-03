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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Core\InstanceConfigTrait;
use Throwable;

/**
 * The FlashMessage class provides a way for you to write a flash variable
 * to the session, to be rendered in a view with the FlashHelper.
 */
class FlashMessage
{
    use InstanceConfigTrait;

    /**
     * Default configuration
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'key' => 'flash',
        'element' => 'default',
        'plugin' => null,
        'params' => [],
        'clear' => false,
        'duplicate' => true,
    ];

    /**
     * @var \Cake\Http\Session
     */
    protected $session;

    /**
     * Constructor
     *
     * @param \Cake\Http\Session $session Session instance.
     * @param array<string, mixed> $config Config array.
     * @see FlashMessage::set() For list of valid config keys.
     */
    public function __construct(Session $session, array $config = [])
    {
        $this->session = $session;
        $this->setConfig($config);
    }

    /**
     * Store flash messages that can be output in the view.
     *
     * If you make consecutive calls to this method, the messages will stack
     * (if they are set with the same flash key)
     *
     * ### Options:
     *
     * - `key` The key to set under the session's Flash key.
     * - `element` The element used to render the flash message. You can use
     *     `'SomePlugin.name'` style value for flash elements from a plugin.
     * - `plugin` Plugin name to use element from.
     * - `params` An array of variables to be made available to the element.
     * - `clear` A bool stating if the current stack should be cleared to start a new one.
     * - `escape` Set to false to allow templates to print out HTML content.
     *
     * @param string $message Message to be flashed.
     * @param array<string, mixed> $options An array of options
     * @return void
     * @see FlashMessage::$_defaultConfig For default values for the options.
     */
    public function set($message, array $options = []): void
    {
        $options += (array)$this->getConfig();

        if (isset($options['escape']) && !isset($options['params']['escape'])) {
            $options['params']['escape'] = $options['escape'];
        }

        [$plugin, $element] = pluginSplit($options['element']);
        if ($options['plugin']) {
            $plugin = $options['plugin'];
        }

        if ($plugin) {
            $options['element'] = $plugin . '.flash/' . $element;
        } else {
            $options['element'] = 'flash/' . $element;
        }

        $messages = [];
        if (!$options['clear']) {
            $messages = (array)$this->session->read('Flash.' . $options['key']);
        }

        if (!$options['duplicate']) {
            foreach ($messages as $existingMessage) {
                if ($existingMessage['message'] === $message) {
                    return;
                }
            }
        }

        $messages[] = [
            'message' => $message,
            'key' => $options['key'],
            'element' => $options['element'],
            'params' => $options['params'],
        ];

        $this->session->write('Flash.' . $options['key'], $messages);
    }

    /**
     * Set an exception's message as flash message.
     *
     * The following options will be set by default if unset:
     * ```
     * 'element' => 'error',
     * `params' => ['code' => $exception->getCode()]
     * ```
     *
     * @param \Throwable $exception Exception instance.
     * @param array<string, mixed> $options An array of options.
     * @return void
     * @see FlashMessage::set() For list of valid options
     */
    public function setExceptionMessage(Throwable $exception, array $options = []): void
    {
        $options['element'] = $options['element'] ?? 'error';
        $options['params']['code'] = $options['params']['code'] ?? $exception->getCode();

        $message = $exception->getMessage();
        $this->set($message, $options);
    }

    /**
     * Get the messages for given key and remove from session.
     *
     * @param string $key The key for get messages for.
     * @return array|null
     */
    public function consume(string $key): ?array
    {
        return $this->session->consume("Flash.{$key}");
    }

    /**
     * Set a success message.
     *
     * The `'element'` option will be set to  `'success'`.
     *
     * @param string $message Message to flash.
     * @param array<string, mixed> $options An array of options.
     * @return void
     * @see FlashMessage::set() For list of valid options
     */
    public function success(string $message, array $options = []): void
    {
        $options['element'] = 'success';
        $this->set($message, $options);
    }

    /**
     * Set an success message.
     *
     * The `'element'` option will be set to  `'error'`.
     *
     * @param string $message Message to flash.
     * @param array<string, mixed> $options An array of options.
     * @return void
     * @see FlashMessage::set() For list of valid options
     */
    public function error(string $message, array $options = []): void
    {
        $options['element'] = 'error';
        $this->set($message, $options);
    }

    /**
     * Set a warning message.
     *
     * The `'element'` option will be set to  `'warning'`.
     *
     * @param string $message Message to flash.
     * @param array<string, mixed> $options An array of options.
     * @return void
     * @see FlashMessage::set() For list of valid options
     */
    public function warning(string $message, array $options = []): void
    {
        $options['element'] = 'warning';
        $this->set($message, $options);
    }

    /**
     * Set an info message.
     *
     * The `'element'` option will be set to  `'info'`.
     *
     * @param string $message Message to flash.
     * @param array<string, mixed> $options An array of options.
     * @return void
     * @see FlashMessage::set() For list of valid options
     */
    public function info(string $message, array $options = []): void
    {
        $options['element'] = 'info';
        $this->set($message, $options);
    }
}
