<?php
declare(strict_types=1);

namespace TestPluginTwo\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIoInterface;

class WelcomeSayHelloCommand extends Command
{
    public static function defaultName(): string
    {
        return 'welcome say_hello';
    }

    public function execute(Arguments $args, ConsoleIoInterface $io)
    {
    }
}
