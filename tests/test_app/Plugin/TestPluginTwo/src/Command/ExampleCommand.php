<?php
declare(strict_types=1);

namespace TestPluginTwo\Command;

use Cake\Command\Command;

class ExampleCommand extends Command
{
    public function execute(): void
    {
        $this->io->out('This is the main method called from TestPluginTwo.ExampleCommand');
    }
}
