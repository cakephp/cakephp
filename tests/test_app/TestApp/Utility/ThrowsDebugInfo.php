<?php
declare(strict_types=1);

namespace TestApp\Utility;

use Exception;

class ThrowsDebugInfo
{
    /**
     * @inheritDoc
     */
    public function __debugInfo()
    {
        throw new Exception('from __debugInfo');
    }
}
