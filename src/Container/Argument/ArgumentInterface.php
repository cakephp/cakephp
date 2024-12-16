<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

interface ArgumentInterface
{
    /**
     * @return mixed
     */
    public function getValue(): mixed;
}
