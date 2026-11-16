<?php
declare(strict_types=1);

namespace TestPluginTwo\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('example')]
class ExampleCommand extends Command
{
    public function execute(): void
    {
        $this->io->out('This is the main method called from TestPluginTwo.ExampleCommand');
    }
}
