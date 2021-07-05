<?php
declare(strict_types=1);

namespace TestApp\Http\Session;

use Cake\Http\Session;

/**
 * Overwrite Session to simulate a web session even if the test runs on CLI.
 */
class TestWebSession extends Session
{
    protected function _hasSession(): bool
    {
        $isCLI = $this->_isCLI;
        $this->_isCLI = false;

        $result = parent::_hasSession();

        $this->_isCLI = $isCLI;

        return $result;
    }
}
