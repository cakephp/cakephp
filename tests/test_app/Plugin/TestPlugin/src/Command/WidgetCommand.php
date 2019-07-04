<?php
declare(strict_types=1);

namespace TestPlugin\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;

class WidgetCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('Widgets!');
    }
}
