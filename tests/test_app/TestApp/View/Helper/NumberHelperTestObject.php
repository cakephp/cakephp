<?php
declare(strict_types=1);

namespace TestApp\View\Helper;

use Cake\View\Helper\NumberHelper;
use TestApp\I18n\NumberMock;

/**
 * NumberHelperTestObject class
 */
class NumberHelperTestObject extends NumberHelper
{
    public function attach(NumberMock $cakeNumber): void
    {
        $this->_engine = $cakeNumber;
    }

    /**
     * @return mixed
     */
    public function engine()
    {
        return $this->_engine;
    }
}
