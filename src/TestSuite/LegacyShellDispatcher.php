<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Console\ShellDispatcher;

/**
 * Allows injecting mock IO into shells
 */
class LegacyShellDispatcher extends ShellDispatcher
{
    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $_io;

    /**
     * Constructor
     *
     * @param array $args Argument array
     * @param bool $bootstrap Initialize environment
     * @param \Cake\Console\ConsoleIo $io ConsoleIo
     */
    public function __construct($args = [], $bootstrap = true, $io = null)
    {
        $this->_io = $io;
        parent::__construct($args, $bootstrap);
    }

    /**
     * Injects mock and stub io components into the shell
     *
     * @param string $className Class name
     * @param string $shortName Short name
     * @return \Cake\Console\Shell
     */
    protected function _createShell($className, $shortName)
    {
        list($plugin) = pluginSplit($shortName);
        $instance = new $className($this->_io);
        $instance->plugin = trim($plugin, '.');

        return $instance;
    }
}
