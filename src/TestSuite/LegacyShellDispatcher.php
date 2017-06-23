<?php
namespace Cake\TestSuite;

use Cake\Console\ShellDispatcher;

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
    public function __construct($args = array(), $bootstrap = true, $io)
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
