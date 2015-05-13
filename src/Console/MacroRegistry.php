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

use Cake\Console\Exception\MissingMacroException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use Cake\Console\ConsoleIo;

/**
 * Registry for Macros. Provides features
 * for lazily loading macros.
 */
class MacroRegistry extends ObjectRegistry
{

    /**
     * Shell to use to set params to tasks.
     *
     * @var \Cake\Console\ConsoleIo
     */
    protected $_io;

    /**
     * Constructor
     *
     * @param Shell $Shell Shell instance
     */
    public function __construct(ConsoleIo $io)
    {
        $this->_io = $io;
    }

    /**
     * Resolve a macro classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|false Either the correct classname or false.
     */
    protected function _resolveClassName($class)
    {
        return App::className($class, 'Shell/Macro', 'Macro');
    }

    /**
     * Throws an exception when a macro is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing.
     * @param string $plugin The plugin the macro is missing in.
     * @return void
     * @throws \Cake\Console\Exception\MissingMacroException
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        throw new MissingMacroException([
            'class' => $class,
            'plugin' => $plugin
        ]);
    }

    /**
     * Create the macro instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname to create.
     * @param string $alias The alias of the macro.
     * @param array $settings An array of settings to use for the macro.
     * @return \Cake\Console\Macro The constructed macro class.
     */
    protected function _create($class, $alias, $settings)
    {
        return new $class($this->_io);
    }
}
