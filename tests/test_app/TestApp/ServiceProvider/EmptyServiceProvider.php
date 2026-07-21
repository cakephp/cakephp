<?php
declare(strict_types=1);

namespace TestApp\ServiceProvider;

use Cake\Container\ContainerInterface;
use Cake\Core\ServiceProvider;

class EmptyServiceProvider extends ServiceProvider
{
    protected array $provides = [];

    public function services(ContainerInterface $container): void
    {
    }
}
