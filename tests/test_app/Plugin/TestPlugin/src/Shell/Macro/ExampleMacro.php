<?php
namespace TestPlugin\Shell\Macro;

use Cake\Console\Macro;

class ExampleMacro extends Macro
{
    public function output($args)
    {
        $this->_io->out('Plugins work!');
    }
}
