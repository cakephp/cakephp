<?php
declare(strict_types=1);

namespace TestPlugin\Shell\Helper;

use Cake\Console\Helper;

class ExampleHelper extends Helper
{
    public function output(array $args): void
    {
        $this->_io->out('Plugins work!');
    }
}
