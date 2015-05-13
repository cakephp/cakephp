<?php
namespace TestApp\Shell\Macro;

use Cake\Console\Macro;

class SimpleMacro extends Macro
{
    public function output($args)
    {
        $this->_io->out('It works!');
    }
}
