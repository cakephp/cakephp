<?php
namespace TestApp\Auth;

use Cake\Auth\AbstractPasswordHasher;
use InvalidArgumentException;

class CallCounterPasswordHasher extends AbstractPasswordHasher
{
    public $callCount = 0;

    /**
     * @inheritDoc
     */
    public function hash($password)
    {
        $this->callCount++;

        return 'hash123';
    }

    /**
     * @inheritDoc
     */
    public function check($password, $hashedPassword)
    {
        if ($hashedPassword == null || $hashedPassword === '') {
            throw new InvalidArgumentException('Empty hash not expected');
        }

        $this->callCount++;

        return false;
    }
}
