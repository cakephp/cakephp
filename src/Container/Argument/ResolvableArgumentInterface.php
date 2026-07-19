<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

interface ResolvableArgumentInterface extends ArgumentInterface
{
    /**
     * @return string
     */
    public function getValue(): string;
}
