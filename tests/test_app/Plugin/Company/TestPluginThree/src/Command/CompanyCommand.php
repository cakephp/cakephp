<?php
declare(strict_types=1);

namespace Company\TestPluginThree\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('company')]
class CompanyCommand extends Command
{
    public function execute()
    {
    }
}
