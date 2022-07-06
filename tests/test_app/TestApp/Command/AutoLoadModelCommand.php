<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class AutoLoadModelCommand extends Command
{
    protected ?string $defaultTable = 'Posts';

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        return static::CODE_SUCCESS;
    }
}
