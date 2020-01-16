<?php
declare(strict_types=1);

namespace TestApp\Auth;

use Cake\Auth\AbstractPasswordHasher;
use InvalidArgumentException;

class CallCounterPasswordHasher extends AbstractPasswordHasher
{
    public $callCount = 0;

    /**
     * @inheritDoc
     */
    public function hash(string $password)
    {
        $this->callCount++;

        return 'hash123';
    }

    /**
     * @inheritDoc
     */
    public function check(string $password, string $hashedPassword): bool
    {
        if ($hashedPassword === '') {
            throw new InvalidArgumentException('Empty hash not expected');
        }

        $this->callCount++;

        return false;
    }
}
