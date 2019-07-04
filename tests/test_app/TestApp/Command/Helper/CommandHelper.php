<?php
declare(strict_types=1);

namespace TestApp\Command\Helper;

use Cake\Console\Helper;

class CommandHelper extends Helper
{
    public function output(array $args): void
    {
        $this->_io->out('I am helping ' . implode(' ', $args));
    }
}
