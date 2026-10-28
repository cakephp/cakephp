<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;

class SampleCommand extends Command
{
    public function execute(): void
    {
        $this->io->out('This is the main method called from SampleCommand');
    }
}
