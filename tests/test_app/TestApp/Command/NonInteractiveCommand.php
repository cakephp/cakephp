<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('non_interactive')]
class NonInteractiveCommand extends Command
{
    public function execute(): void
    {
        $result = $this->io->ask('What?', 'Default!');
        $this->io->quiet('Result: ' . $result);
    }
}
