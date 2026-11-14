<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('format_specifier')]
class FormatSpecifierCommand extends Command
{
    public function execute(): void
    {
        $this->io->out('Be careful! %s is a format specifier!');
    }
}
