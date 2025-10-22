<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use stdClass;

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
