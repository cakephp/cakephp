<?php
declare(strict_types=1);

namespace TestApp\Stub;

use Cake\ORM\Locator\LocatorAwareTrait;

class LocatorAwareStub
{
    use LocatorAwareTrait;

    public function __construct(?string $defaultTable = null)
    {
        $this->defaultTable = $defaultTable;
    }
}
