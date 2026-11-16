<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\CommandHiddenInterface;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('hidden')]
class HiddenCommand extends Command implements CommandHiddenInterface
{
    public static function getDescription(): string
    {
        return 'This command should not appear in help';
    }

    public function execute(): int
    {
        $this->io->out('Hidden Command Executed!');

        return static::CODE_SUCCESS;
    }
}
