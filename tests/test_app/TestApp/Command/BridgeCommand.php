<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class BridgeCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $name = $io->ask('What is your name');

        if ($name !== 'cake') {
            $io->err('No!');

            return static::CODE_ERROR;
        }

        $color = $io->ask('What is your favorite color?');

        if ($color !== 'blue') {
            $io->err('Wrong! <blink>Aaaahh</blink>');

            return static::CODE_ERROR;
        }

        $io->out('You may pass.');
    }
}
