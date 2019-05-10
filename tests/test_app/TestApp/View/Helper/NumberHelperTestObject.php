<?php
declare(strict_types=1);

namespace TestApp\View\Helper;

use Cake\View\Helper\NumberHelper;
use TestApp\Utility\NumberMock;

/**
 * NumberHelperTestObject class
 */
class NumberHelperTestObject extends NumberHelper
{
    public function attach(NumberMock $cakeNumber)
    {
        $this->_engine = $cakeNumber;
    }

    public function engine()
    {
        return $this->_engine;
    }
}
