<?php
declare(strict_types=1);

namespace TestPluginTwo\Command;

use Cake\Command\Command;

class ExampleCommand extends Command
{
    public function execute()
    {
        $this->io->out('This is the main method called from TestPluginTwo.ExampleCommand');
    }
}
