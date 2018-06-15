<?php
namespace TestApp\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;

class AbortCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->error('Command aborted');
        $this->abort(127);
    }
}
