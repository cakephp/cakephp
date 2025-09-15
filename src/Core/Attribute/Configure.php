<?php
declare(strict_types=1);

namespace Cake\Core\Attribute;

use Attribute;
use Cake\Core\Configure as CakeConfigure;
use League\Container\Attribute\AttributeInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Configure implements AttributeInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param string $name
     */
    public function __construct(private string $name)
    {
    }

    /**
     * @return mixed
     */
    public function resolve(): mixed
    {
        return CakeConfigure::read($this->name);
    }
}
