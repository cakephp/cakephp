<?php
declare(strict_types=1);

namespace TestApp\Command\Helper;

use Cake\Console\Helper;

class SimpleHelper extends Helper
{
    public function output(array $args): void
    {
        $this->_io->out('It works!' . implode(' ', $args));
    }
}
