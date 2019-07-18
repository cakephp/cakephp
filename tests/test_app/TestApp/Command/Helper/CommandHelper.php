<?php
namespace TestApp\Command\Helper;

use Cake\Console\Helper;

class CommandHelper extends Helper
{
    public function output($args)
    {
        $this->_io->out('I am helping ' . implode(' ', $args));
    }
}
