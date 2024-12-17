<?php
declare(strict_types=1);

namespace Cake\Container\Argument;

use Cake\Container\ContainerAwareInterface;
use ReflectionFunctionAbstract;

interface ArgumentResolverInterface extends ContainerAwareInterface
{
    /**
     * @param array<\Cake\Container\Argument\LiteralArgument|\Cake\Container\Argument\ResolvableArgument> $arguments
     * @return array
     */
    public function resolveArguments(array $arguments): array;

    /**
     * @param \ReflectionFunctionAbstract $method
     * @param array $args
     * @return array
     */
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []): array;
}
