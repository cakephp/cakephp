<?php
declare(strict_types=1);

namespace TestPluginTwo\Command;

use Cake\Command\Command;
use Cake\Console\Attribute\AsCommand;

/**
 * Command fixture for console integration tests.
 */
#[AsCommand('welcome say_hello')]
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
