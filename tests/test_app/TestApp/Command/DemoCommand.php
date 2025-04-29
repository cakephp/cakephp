<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIoInterface;

class DemoCommand extends Command
{
    public static function getDescription(): string
    {
        return 'This is a demo command';
    }

    public function execute(Arguments $args, ConsoleIoInterface $io): ?int
    {
        $io->quiet('Quiet!');
        $io->out('Demo Command!');
        $io->verbose('Verbose!');
        if ($args->hasArgumentAt(0)) {
            $io->out($args->getArgumentAt(0));
        }

        return null;
    }
}
