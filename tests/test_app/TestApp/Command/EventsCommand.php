<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIoInterface;
use Cake\Event\EventInterface;

class EventsCommand extends Command
{
    public static function getDescription(): string
    {
        return 'This is a command that uses events';
    }

    public function execute(): ?int
    {
        $this->io->out('execute run');

        return null;
    }

    public function beforeExecute(EventInterface $event, Arguments $args, ConsoleIoInterface $io): void
    {
        $this->io->out('beforeExecute run');
    }

    public function afterExecute(EventInterface $event, Arguments $args, ConsoleIoInterface $io, mixed $result): void
    {
        $this->io->out('afterExecute run');
    }
}
