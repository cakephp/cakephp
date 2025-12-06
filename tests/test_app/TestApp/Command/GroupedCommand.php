<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class GroupedCommand extends Command
{
    public static function getGroup(): string
    {
        return 'custom_group';
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Grouped Command!');
    }
}
