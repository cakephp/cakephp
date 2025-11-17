<?php
declare(strict_types=1);

namespace TestPluginTwo\Command;

use Cake\Command\Command;

class WelcomeSayHelloCommand extends Command
{
    public static function defaultName(): string
    {
        return 'welcome say_hello';
    }

    public function execute()
    {
    }
}
