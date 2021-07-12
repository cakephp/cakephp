<?php
declare(strict_types=1);

namespace TestPluginTwo\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class ExampleCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('This is the main method called from TestPluginTwo.ExampleCommand');
    }
}
