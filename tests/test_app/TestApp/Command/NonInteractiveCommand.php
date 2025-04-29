<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIoInterface;

class NonInteractiveCommand extends Command
{
    public function execute(Arguments $args, ConsoleIoInterface $io)
    {
        $result = $io->ask('What?', 'Default!');
        $io->quiet('Result: ' . $result);

        return null;
    }
}
