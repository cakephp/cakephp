<?php
declare(strict_types=1);
namespace TestApp\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;

class DemoCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
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
