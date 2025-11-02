<?php
declare(strict_types=1);

namespace TestApp\Service;

/**
 * A simple test service for dependency injection testing
 */
class TestService
{
    public function getName(): string
    {
        return 'TestService';
    }
}
