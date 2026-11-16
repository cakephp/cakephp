<?php
declare(strict_types=1);

namespace TestPlugin\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('example')]
class ExampleCommand extends Command
{
    public function execute()
    {
    }
}
