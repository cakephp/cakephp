<?php
namespace TestApp\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;

class DemoCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->quiet('Quiet!');
        $io->out('Demo Command!');
        $io->verbose('Verbose!');
    }
}
