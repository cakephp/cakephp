<?php
declare(strict_types=1);

namespace TestApp\ServiceProvider;

use Cake\Container\DefinitionContainerInterface;
use Cake\Core\ServiceProvider;

class EmptyServiceProvider extends ServiceProvider
{
    protected array $provides = [];

    public function services(DefinitionContainerInterface $container): void
    {
    }
}
