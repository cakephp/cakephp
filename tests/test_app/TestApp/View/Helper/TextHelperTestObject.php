<?php
declare(strict_types=1);

namespace TestApp\View\Helper;

use Cake\View\Helper\TextHelper;
use TestApp\Utility\TextMock;

class TextHelperTestObject extends TextHelper
{
    public function attach(TextMock $string)
    {
        $this->_engine = $string;
    }

    public function engine()
    {
        return $this->_engine;
    }
}
