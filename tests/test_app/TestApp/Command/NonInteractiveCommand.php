<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;

class NonInteractiveCommand extends Command
{
    public function execute()
    {
        $result = $this->io->ask('What?', 'Default!');
        $this->io->quiet('Result: ' . $result);
    }
}
