<?php
declare(strict_types=1);

namespace TestApp\Shell\Helper;

use Cake\Console\Helper;

class SimpleHelper extends Helper
{
    public function output(array $args): void
    {
        $this->_io->out('It works!' . implode(' ', $args));
    }
}
