<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;

class SampleCommand extends Command
{
    public function execute()
    {
        $this->io->out('This is the main method called from SampleCommand');
    }
}
