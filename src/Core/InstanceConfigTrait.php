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
namespace Cake\Core;

use Cake\Core\Exception\Exception;
use Cake\Utility\Hash;
use InvalidArgumentException;

/**
 * A trait for reading and writing instance config
 *
 * Implementing objects are expected to declare a `$_defaultConfig` property.
 */
trait InstanceConfigTrait
{
    /**
     * Runtime config
     *
     * @var array
     */
    protected $_config = [];

    /**
     * Whether the config property has already been configured with defaults
     *
     * @var bool
     */
    protected $_configInitialized = false;

    /**
     * Sets the config.
     *
     * ### Usage
     *
     * Setting a specific value:
     *
     * ```
     * $this->setConfig('key', $value);
     * ```
     *
     * Setting a nested value:
     *
     * ```
     * $this->setConfig('some.nested.key', $value);
     * ```
     *
     * Updating multiple config settings at the same time:
     *
     * ```
     * $this->setConfig(['one' => 'value', 'another' => 'value']);
     * ```
     *
     * @param string|array $key The key to set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @param bool $merge Whether to recursively merge or overwrite existing config, defaults to true.
     * @return $this
     * @throws \Cake\Core\Exception\Exception When trying to set a key that is invalid.
     */
    public function setConfig($key, $value = null, $merge = true)
    {
        if (!$this->_configInitialized) {
            $this->_config = $this->_defaultConfig;
            $this->_configInitialized = true;
        }

        $this->_configWrite($key, $value, $merge);

        return $this;
    }

    /**
     * Returns the config.
     *
     * ### Usage
     *
     * Reading the whole config:
     *
     * ```
     * $this->getConfig();
     * ```
     *
     * Reading a specific value:
     *
     * ```
     * $this->getConfig('key');
     * ```
     *
     * Reading a nested value:
     *
     * ```
     * $this->getConfig('some.nested.key');
     * ```
     *
     * Reading with default value:
     *
     * ```
     * $this->getConfig('some-key', 'default-value');
     * ```
     *
     * @param string|null $key The key to get or null for the whole config.
     * @param mixed $default The return value when the key does not exist.
     * @return mixed Configuration data at the named key or null if the key does not exist.
     */
    public function getConfig(?string $key = null, $default = null)
    {
        if (!$this->_configInitialized) {
            $this->_config = $this->_defaultConfig;
            $this->_configInitialized = true;
        }

        $return = $this->_configRead($key);

        return $return ?? $default;
    }

    /**
     * Returns the config for this specific key.
     *
     * The config value for this key must exist, it can never be null.
     *
     * @param string $key The key to get.
     * @return mixed Configuration data at the named key
     * @throws \InvalidArgumentException
     */
    public function getConfigOrFail(string $key)
    {
        $config = $this->getConfig($key);
        if ($config === null) {
            throw new InvalidArgumentException(sprintf('Expected configuration `%s` not found.', $key));
        }

        return $config;
    }

    /**
     * Merge provided config with existing config. Unlike `config()` which does
     * a recursive merge for nested keys, this method does a simple merge.
     *
     * Setting a specific value:
     *
     * ```
     * $this->configShallow('key', $value);
     * ```
     *
     * Setting a nested value:
     *
     * ```
     * $this->configShallow('some.nested.key', $value);
     * ```
     *
     * Updating multiple config settings at the same time:
     *
     * ```
     * $this->configShallow(['one' => 'value', 'another' => 'value']);
     * ```
     *
     * @param string|array $key The key to set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @return $this
     */
    public function configShallow($key, $value = null)
    {
        if (!$this->_configInitialized) {
            $this->_config = $this->_defaultConfig;
            $this->_configInitialized = true;
        }

        $this->_configWrite($key, $value, 'shallow');

        return $this;
    }

    /**
     * Reads a config key.
     *
     * @param string|null $key Key to read.
     * @return mixed
     */
    protected function _configRead(?string $key)
    {
        if ($key === null) {
            return $this->_config;
        }

        if (strpos($key, '.') === false) {
            return $this->_config[$key] ?? null;
        }

        $return = $this->_config;

        foreach (explode('.', $key) as $k) {
            if (!is_array($return) || !isset($return[$k])) {
                $return = null;
                break;
            }

            $return = $return[$k];
        }

        return $return;
    }

    /**
     * Writes a config key.
     *
     * @param string|array $key Key to write to.
     * @param mixed $value Value to write.
     * @param bool|string $merge True to merge recursively, 'shallow' for simple merge,
     *   false to overwrite, defaults to false.
     * @return void
     * @throws \Cake\Core\Exception\Exception if attempting to clobber existing config
     */
    protected function _configWrite($key, $value, $merge = false): void
    {
        if (is_string($key) && $value === null) {
            $this->_configDelete($key);

            return;
        }

        if ($merge) {
            $update = is_array($key) ? $key : [$key => $value];
            if ($merge === 'shallow') {
                $this->_config = array_merge($this->_config, Hash::expand($update));
            } else {
                $this->_config = Hash::merge($this->_config, Hash::expand($update));
            }

            return;
        }

        if (is_array($key)) {
            foreach ($key as $k => $val) {
                $this->_configWrite($k, $val);
            }

            return;
        }

        if (strpos($key, '.') === false) {
            $this->_config[$key] = $value;

            return;
        }

        $update = &$this->_config;
        $stack = explode('.', $key);

        foreach ($stack as $k) {
            if (!is_array($update)) {
                throw new Exception(sprintf('Cannot set %s value', $key));
            }

            if (!isset($update[$k])) {
                $update[$k] = [];
            }

            $update = &$update[$k];
        }

        $update = $value;
    }

    /**
     * Deletes a single config key.
     *
     * @param string $key Key to delete.
     * @return void
     * @throws \Cake\Core\Exception\Exception if attempting to clobber existing config
     */
    protected function _configDelete(string $key): void
    {
        if (strpos($key, '.') === false) {
            unset($this->_config[$key]);

            return;
        }

        $update = &$this->_config;
        $stack = explode('.', $key);
        $length = count($stack);

        foreach ($stack as $i => $k) {
            if (!is_array($update)) {
                throw new Exception(sprintf('Cannot unset %s value', $key));
            }

            if (!isset($update[$k])) {
                break;
            }

            if ($i === $length - 1) {
                unset($update[$k]);
                break;
            }

            $update = &$update[$k];
        }
    }
}
