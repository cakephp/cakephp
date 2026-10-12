<?php
declare(strict_types=1);

namespace TestApp\Command;

use Cake\Command\Command;
use Cake\Console\CommandFactoryInterface;
use TestApp\ReflectionDependency;

class AutoWiredCommand extends Command
{
    public function __construct(
        public ReflectionDependency $dependency,
        ?CommandFactoryInterface $factory = null,
    ) {
        parent::__construct($factory);
    }

    public function getFactory(): ?CommandFactoryInterface
    {
        return $this->factory;
    }

    public function execute(): int
    {
        return static::CODE_SUCCESS;
    }
}
