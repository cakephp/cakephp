<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\CommandHiddenInterface;
use Cake\Console\ConsoleIo;

class HiddenCommand extends Command implements CommandHiddenInterface
{
    public static function getDescription(): string
    {
        return 'This command should not appear in help';
    }

    public function execute(Arguments $args, ConsoleIo $io): void
    {
        $io->out('Hidden Command Executed!');
    }
}
