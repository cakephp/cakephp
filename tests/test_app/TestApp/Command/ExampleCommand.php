<?php
namespace TestApp\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;

class ExampleCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->quiet('Quiet!');
        $io->out('Example Command!');
        $io->verbose('Verbose!');
    }
}
