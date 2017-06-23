<?php
namespace Cake\TestSuite;

use Cake\Console\ShellDispatcher;

class LegacyCommandRunner extends ShellDispatcher
{
    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $_io;

    /**
     * Constructor
     *
     * @param array $args the argv from PHP
     * @param bool $bootstrap Should the environment be bootstrapped.
     * @param \Cake\Console\ConsoleIo $io The ConsoleIo class to use.
     * @return void
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
