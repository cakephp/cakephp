<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;
use stdClass;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('dependency')]
class DependencyCommand extends Command
{
    public function __construct(
        public stdClass $inject,
    ) {
    }

    public function execute(): int
    {
        $this->io->out('Dependency Command');
        $this->io->out('constructor inject: ' . json_encode($this->inject));

        return static::CODE_SUCCESS;
    }
}
