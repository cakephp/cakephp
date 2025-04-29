<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIoInterface;

class FormatSpecifierCommand extends Command
{
    public function execute(Arguments $args, ConsoleIoInterface $io)
    {
        $io->out('Be careful! %s is a format specifier!');
    }
}
