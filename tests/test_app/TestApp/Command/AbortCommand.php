<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;

class AbortCommand extends Command
{
    public function execute()
    {
        $this->io->error('Command aborted');
        $this->abort(127);
    }
}
