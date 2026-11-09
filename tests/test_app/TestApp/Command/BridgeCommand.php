<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('bridge')]
class BridgeCommand extends Command
{
    public function execute()
    {
        $name = $this->io->ask('What is your name');

        if ($name !== 'cake') {
            $this->io->err('No!');

            return static::CODE_ERROR;
        }

        $color = $this->io->ask('What is your favorite color?');

        if ($color !== 'blue') {
            $this->io->err('Wrong! <blink>Aaaahh</blink>');

            return static::CODE_ERROR;
        }

        $this->io->out('You may pass.');
    }
}
