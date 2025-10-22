<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;

class GroupedCommand extends Command
{
    public static function getGroup(): string
    {
        return 'custom_group';
    }

    public function execute()
    {
        $this->io->out('Grouped Command!');
    }
}
