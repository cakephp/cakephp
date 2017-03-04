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
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\MissingHelperException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;

/**
 * Registry for Helpers. Provides features
 * for lazily loading helpers.
 */
class HelperRegistry extends ObjectRegistry
{

    /**
     * Shell to use to set params to tasks.
     *
     * @var \Cake\Console\ConsoleIo
     */
    protected $_io;

    /**
     * Sets The IO instance that should be passed to the shell helpers
     *
     * @param \Cake\Console\ConsoleIo $io An io instance.
     * @return void
     */
    public function setIo(ConsoleIo $io)
    {
        $this->_io = $io;
    }

    /**
     * Resolve a helper classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|false Either the correct classname or false.
     */
    protected function _resolveClassName($class)
    {
        return App::className($class, 'Shell/Helper', 'Helper');
    }

    /**
     * Throws an exception when a helper is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     * and Cake\Core\ObjectRegistry::unload()
     *
     * @param string $class The classname that is missing.
     * @param string $plugin The plugin the helper is missing in.
     * @return void
     * @throws \Cake\Console\Exception\MissingHelperException
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        throw new MissingHelperException([
            'class' => $class,
            'plugin' => $plugin
        ]);
    }

    /**
     * Create the helper instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname to create.
     * @param string $alias The alias of the helper.
     * @param array $settings An array of settings to use for the helper.
     * @return \Cake\Console\Helper The constructed helper class.
     */
    protected function _create($class, $alias, $settings)
    {
        return new $class($this->_io, $settings);
    }
}
