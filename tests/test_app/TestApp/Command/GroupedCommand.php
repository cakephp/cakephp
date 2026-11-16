<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('grouped')]
class GroupedCommand extends Command
{
    public static function getGroup(): string
    {
        return 'custom_group';
    }

    public function execute(): void
    {
        $this->io->out('Grouped Command!');
    }
}
