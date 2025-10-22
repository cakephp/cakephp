<?php
declare(strict_types=1);

namespace TestPluginTwo\Command;

use Cake\Command\Command;

class WelcomeCommand extends Command
{
    public function execute()
    {
        $this->io->out('This is the say_hello method called from TestPluginTwo.WelcomeCommand');
    }
}
