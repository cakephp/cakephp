<?php
namespace TestPlugin\Shell\Helper;

use Cake\Console\Helper;

class ExampleHelper extends Helper
{
    public function output(array $args)
    {
        $this->_io->out('Plugins work!');
    }
}
