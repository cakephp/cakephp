<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

interface DefaultValueInterface extends ArgumentInterface
{
    /**
     * @return mixed
     */
    public function getDefaultValue(): mixed;
}
