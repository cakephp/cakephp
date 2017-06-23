<?php
namespace Cake\TestSuite;

use Cake\Console\ConsoleIo;

class LegacyCommandRunner
{
    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $_io;

    /**
     * Mimics functionality of Cake\Console\CommandRunner
     *
     * @param array $argv Argument array
     * @param ConsoleIo $io ConsoleIo
     */
    public function run(array $argv, ConsoleIo $io = null)
    {
        $dispatcher = new LegacyShellDispatcher($argv, true, $io);
        return $dispatcher->dispatch();
    }
}
