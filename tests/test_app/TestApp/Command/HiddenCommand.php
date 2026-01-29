<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\CommandHiddenInterface;

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
