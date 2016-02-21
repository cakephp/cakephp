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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\MissingTaskException;
use Cake\Core\App;
use Cake\Core\ObjectRegistry;

/**
 * Registry for Tasks. Provides features
 * for lazily loading tasks.
 */
class TaskRegistry extends ObjectRegistry
{

    /**
     * Shell to use to set params to tasks.
     *
     * @var \Cake\Console\Shell
     */
    protected $_Shell;

    /**
     * Constructor
     *
     * @param \Cake\Console\Shell $Shell Shell instance
     */
    public function __construct(Shell $Shell)
    {
        $this->_Shell = $Shell;
    }

    /**
     * Resolve a task classname.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class Partial classname to resolve.
     * @return string|false Either the correct classname or false.
     */
    protected function _resolveClassName($class)
    {
        return App::className($class, 'Shell/Task', 'Task');
    }

    /**
     * Throws an exception when a task is missing.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname that is missing.
     * @param string $plugin The plugin the task is missing in.
     * @return void
     * @throws \Cake\Console\Exception\MissingTaskException
     */
    protected function _throwMissingClassError($class, $plugin)
    {
        throw new MissingTaskException([
            'class' => $class,
            'plugin' => $plugin
        ]);
    }

    /**
     * Create the task instance.
     *
     * Part of the template method for Cake\Core\ObjectRegistry::load()
     *
     * @param string $class The classname to create.
     * @param string $alias The alias of the task.
     * @param array $settings An array of settings to use for the task.
     * @return \Cake\Console\Shell The constructed task class.
     */
    protected function _create($class, $alias, $settings)
    {
        return new $class($this->_Shell->io());
    }
}
