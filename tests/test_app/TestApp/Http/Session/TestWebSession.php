<?php
declare(strict_types=1);

namespace TestApp\Http\Session;

use Cake\Http\Session;

/**
 * Overwrite Session to simulate a web session even if the test runs on CLI.
 */
class TestWebSession extends Session
{
    protected function hasSession(): bool
    {
        $isCLI = $this->isCLI;
        $this->isCLI = false;

        $result = parent::hasSession();

        $this->isCLI = $isCLI;

        return $result;
    }
}
