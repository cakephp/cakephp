<?php
declare(strict_types=1);

namespace TestApp\Console\Helper;

use Cake\Console\Helper;

class SimpleHelper extends Helper
{
    public function output(array $args): void
    {
        $this->io->out('It works!' . implode(' ', $args));
    }
}
