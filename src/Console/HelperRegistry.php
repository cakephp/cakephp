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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\MissingHelperException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;

/**
 * Registry for Helpers. Provides features
 * for lazily loading helpers.
 *
 * @extends \Cake\Core\ObjectRegistry<\Cake\Console\Helper>
 */
class HelperRegistry extends ObjectRegistry
{
    /**
     * IO instance.
     *
     * @var \Cake\Console\ConsoleIo
     */
    protected ConsoleIo $_io;

    /**
     * Sets The IO instance that should be passed to the shell helpers
     *
     * @param \Cake\Console\ConsoleIo $io An io instance.
     * @return void
     */
    public function setIo(ConsoleIo $io): void
    {
        $this->_io = $io;
    }

    /**
     * Resolve a helper classname.
     *
     * Part of the template method for {@link \Cake\Core\ObjectRegistry::load()}.
     *
     * @param string $class Partial classname to resolve.
     * @return class-string<\Cake\Console\Helper>|null Either the correct class name or null.
     */
    protected function _resolveClassName(string $class): ?string
    {
        /** @var class-string<\Cake\Console\Helper>|null */
        return App::className($class, 'Command/Helper', 'Helper');
    }

    /**
     * Throws an exception when a helper is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     * and Cake\Core\ObjectRegistry::unload()
     *
     * @param string $class The classname that is missing.
     * @param string|null $plugin The plugin the helper is missing in.
     * @return void
     * @throws \Cake\Console\Exception\MissingHelperException
     */
    protected function _throwMissingClassError(string $class, ?string $plugin): void
    {
        throw new MissingHelperException([
            'class' => $class,
            'plugin' => $plugin,
        ]);
    }

    /**
     * Create the helper instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param \Cake\Console\Helper|class-string<\Cake\Console\Helper> $class The classname to create.
     * @param string $alias The alias of the helper.
     * @param array<string, mixed> $config An array of settings to use for the helper.
     * @return \Cake\Console\Helper The constructed helper class.
     */
    protected function _create(object|string $class, string $alias, array $config): Helper
    {
        if (is_object($class)) {
            return $class;
        }

        return new $class($this->_io, $config);
    }
}
