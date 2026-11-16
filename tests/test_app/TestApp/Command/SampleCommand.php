<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('sample')]
class SampleCommand extends Command
{
    public function execute(): void
    {
        $this->io->out('This is the main method called from SampleCommand');
    }
}
