<?php
namespace TestPlugin\Shell\Helper;

use Cake\Console\Helper;

class ExampleHelper extends Helper
{
    public function output($args)
    {
        $this->_io->out('Plugins work!');
    }
}
