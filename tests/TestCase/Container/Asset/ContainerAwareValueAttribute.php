<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Container\Asset;

use Attribute;
use Cake\Container\Attribute\AttributeInterface;
use Cake\Container\ContainerAwareInterface;
use Cake\Container\ContainerAwareTrait;

/**
 * Plain (non-contextual) attribute that still needs the container,
 * via the ContainerAwareInterface hook.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class ContainerAwareValueAttribute implements AttributeInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function resolve(): mixed
    {
        return $this->getContainer()->get(Bar::class);
    }
}
