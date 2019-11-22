<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class AbortCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->error('Command aborted');
        $this->abort(127);

        return null;
    }
}
