<?php
declare(strict_types=1);

namespace Cake\Container\Attribute;

/**
 * Interface for PHP attributes that can resolve a constructor/method
 * argument's value during dependency injection.
 */
interface AttributeInterface
{
    /**
     * Resolve the value this attribute represents.
     *
     * @return mixed
     */
    public function resolve(): mixed;
}
