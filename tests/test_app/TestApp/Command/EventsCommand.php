<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Event\EventInterface;

class EventsCommand extends BaseCommand
{
    public static function getDescription(): string
    {
        return 'This is a command that uses events';
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        return null;
    }

    public function beforeFilter(EventInterface $event)
    {
        /** @var ConsoleIo $io */
        $io = $event->getData('io');

        $io->out('beforeFilter run');
    }

    public function beforeExecute(EventInterface $event)
    {
        /** @var ConsoleIo $io */
        $io = $event->getData('io');

        $io->out('beforeExecute run');
    }

    public function afterExecute(EventInterface $event)
    {
        /** @var ConsoleIo $io */
        $io = $event->getData('io');

        $io->out('afterExecute');
    }
}
