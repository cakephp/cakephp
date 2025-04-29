<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIoInterface;
use stdClass;

class DependencyCommand extends Command
{
    public $inject;

    public function __construct(stdClass $inject)
    {
        $this->inject = $inject;
    }

    public function execute(Arguments $args, ConsoleIoInterface $io): int
    {
        $io->out('Dependency Command');
        $io->out('constructor inject: ' . json_encode($this->inject));

        return static::CODE_SUCCESS;
    }
}
