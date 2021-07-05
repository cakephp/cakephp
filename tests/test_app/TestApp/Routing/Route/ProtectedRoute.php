<?php
declare(strict_types=1);

namespace TestApp\Routing\Route;

use Cake\Routing\Route\Route;

/**
 * Used to expose protected methods for testing.
 */
class ProtectedRoute extends Route
{
    /**
     * @param string $url
     * @return array
     */
    public function parseExtension($url): array
    {
        return $this->_parseExtension($url);
    }
}
