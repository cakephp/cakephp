<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIoInterface;

class AbortCommand extends Command
{
    public function execute(Arguments $args, ConsoleIoInterface $io)
    {
        $io->error('Command aborted');
        $this->abort(127);

        return null;
    }
}
