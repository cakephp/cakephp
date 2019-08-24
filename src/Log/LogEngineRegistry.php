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
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log;

use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use Cake\Log\Engine\BaseLog;
use RuntimeException;

/**
 * Registry of loaded log engines
 *
 * @extends \Cake\Core\ObjectRegistry<\Cake\Log\Engine\BaseLog>
 */
class LogEngineRegistry extends ObjectRegistry
{
    /**
     * Resolve a logger classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|null Either the correct class name or null.
     */
    protected function _resolveClassName(string $class): ?string
    {
        return App::className($class, 'Log/Engine', 'Log');
    }

    /**
     * Throws an exception when a logger is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing.
     * @param string|null $plugin The plugin the logger is missing in.
     * @return void
     * @throws \RuntimeException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        throw new RuntimeException(sprintf('Could not load class %s', $class));
    }

    /**
     * Create the logger instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string|\Cake\Log\Engine\BaseLog $class The classname or object to make.
     * @param string $alias The alias of the object.
     * @param array $settings An array of settings to use for the logger.
     * @return \Cake\Log\Engine\BaseLog The constructed logger class.
     * @throws \RuntimeException when an object doesn't implement the correct interface.
     */
    protected function _create($class, string $alias, array $settings): BaseLog
    {
        if (is_callable($class)) {
            $class = $class($alias);
        }

        if (is_object($class)) {
            $instance = $class;
        }

        if (!isset($instance)) {
            /** @var string $class */
            $instance = new $class($settings);
        }

        if ($instance instanceof BaseLog) {
            return $instance;
        }

        throw new RuntimeException('Loggers must instanceof ' . BaseLog::class);
    }

    /**
     * Remove a single logger from the registry.
     *
     * @param string $name The logger name.
     * @return $this
     */
    public function unload(string $name)
    {
        unset($this->_loaded[$name]);

        return $this;
    }
}
