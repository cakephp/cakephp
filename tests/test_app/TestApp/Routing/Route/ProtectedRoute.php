<?php
declare(strict_types=1);

namespace TestApp\Routing\Route;

use Cake\Routing\Route\Route;

/**
 * Used to expose protected methods for testing.
 */
class ProtectedRoute extends Route
{
    public function parseExtension(string $url): array
    {
        return $this->_parseExtension($url);
    }
}
