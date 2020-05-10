<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class NonInteractiveCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $result = $io->ask('What?', 'Default!');
        $io->quiet('Result: ' . $result);

        return null;
    }
}
