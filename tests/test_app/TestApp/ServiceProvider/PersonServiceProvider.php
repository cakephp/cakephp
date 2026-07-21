<?php
declare(strict_types=1);

namespace TestApp\ServiceProvider;

use Cake\Container\DefinitionContainerInterface;
use Cake\Core\ServiceProvider;

class PersonServiceProvider extends ServiceProvider
{
    protected array $provides = ['boot', 'sally'];

    public function bootstrap(DefinitionContainerInterface $container): void
    {
        $container->add('boot', json_decode('{"name":"boot"}'));
    }

    public function services(DefinitionContainerInterface $container): void
    {
        $container->add('sally', json_decode('{"name":"sally"}'));
    }
}
