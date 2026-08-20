<?php
declare(strict_types=1);

namespace TestApp\ServiceProvider;

use Cake\Container\ContainerInterface;
use Cake\Container\ServiceProvider;

class PersonServiceProvider extends ServiceProvider
{
    protected array $provides = ['boot', 'sally'];

    public function bootstrap(ContainerInterface $container): void
    {
        $container->add('boot', json_decode('{"name":"boot"}'));
    }

    public function services(ContainerInterface $container): void
    {
        $container->add('sally', json_decode('{"name":"sally"}'));
    }
}
