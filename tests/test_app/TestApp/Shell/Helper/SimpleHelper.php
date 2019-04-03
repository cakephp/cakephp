<?php
namespace TestApp\Shell\Helper;

use Cake\Console\Helper;

class SimpleHelper extends Helper
{
    public function output($args)
    {
        $this->_io->out('It works!' . implode(' ', $args));
    }
}
