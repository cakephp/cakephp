<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         4.2.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core\TestSuite;

use Cake\Core\Configure;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Core\ContainerInterface;
use Cake\Core\HttpApplicationInterface;
use Cake\Event\EventInterface;
use Closure;
use League\Container\Exception\NotFoundException;
use LogicException;

/**
 * A set of methods used for defining container services
 * in test cases.
 *
 * This trait leverages the `Application.buildContainer` event
 * to inject the mocked services into the container that the
 * application uses.
 */
trait ContainerStubTrait
{
    /**
     * The customized application class name.
     *
     * @psalm-var class-string<\Cake\Core\HttpApplicationInterface>|class-string<\Cake\Core\ConsoleApplicationInterface>|null
     * @var string|null
     */
    protected ?string $_appClass = null;

    /**
     * The customized application constructor arguments.
     *
     * @var array|null
     */
    protected ?array $_appArgs = null;

    /**
     * The collection of container services.
     *
     * @var array
     */
    private array $containerServices = [];

    /**
     * Configure the application class to use in integration tests.
     *
     * @param string $class The application class name.
     * @param array|null $constructorArgs The constructor arguments for your application class.
     * @return void
     * @psalm-param class-string<\Cake\Core\HttpApplicationInterface>|class-string<\Cake\Core\ConsoleApplicationInterface> $class
     */
    public function configApplication(string $class, ?array $constructorArgs): void
    {
        $this->_appClass = $class;
        $this->_appArgs = $constructorArgs;
    }

    /**
     * Create an application instance.
     *
     * Uses the configuration set in `configApplication()`.
     *
     * @return \Cake\Core\HttpApplicationInterface|\Cake\Core\ConsoleApplicationInterface
     */
    protected function createApp(): HttpApplicationInterface|ConsoleApplicationInterface
    {
        if ($this->_appClass) {
            $appClass = $this->_appClass;
        } else {
            /** @var class-string<\Cake\Http\BaseApplication> $appClass */
            $appClass = Configure::read('App.namespace') . '\Application';
        }
        if (!class_exists($appClass)) {
            throw new LogicException(sprintf('Cannot load `%s` for use in integration testing.', $appClass));
        }
        $appArgs = $this->_appArgs ?: [CONFIG];

        $app = new $appClass(...$appArgs);
        if ($this->containerServices && method_exists($app, 'getEventManager')) {
            $app->getEventManager()->on('Application.buildContainer', [$this, 'modifyContainer']);
        }

        return $app;
    }

    /**
     * Add a mocked service to the container.
     *
     * When the container is created the provided classname
     * will be mapped to the factory function. The factory
     * function will be used to create mocked services.
     *
     * @param string $class The class or interface you want to define.
     * @param \Closure $factory The factory function for mocked services.
     * @return $this
     */
    public function mockService(string $class, Closure $factory)
    {
        $this->containerServices[$class] = $factory;

        return $this;
    }

    /**
     * Remove a mocked service to the container.
     *
     * @param string $class The class or interface you want to remove.
     * @return $this
     */
    public function removeMockService(string $class)
    {
        unset($this->containerServices[$class]);

        return $this;
    }

    /**
     * Wrap the application's container with one containing mocks.
     *
     * If any mocked services are defined, the application's container
     * will be replaced with one containing mocks. The original
     * container will be set as a delegate to the mock container.
     *
     * @param \Cake\Event\EventInterface $event The event
     * @param \Cake\Core\ContainerInterface $container The container to wrap.
     * @return void
     */
    public function modifyContainer(EventInterface $event, ContainerInterface $container): void
    {
        if (!$this->containerServices) {
            return;
        }
        foreach ($this->containerServices as $key => $factory) {
            if ($container->has($key)) {
                try {
                    $container->extend($key)->setConcrete($factory);
                } catch (NotFoundException $e) {
                    $container->add($key, $factory);
                }
            } else {
                $container->add($key, $factory);
            }
        }

        $event->setResult($container);
    }

    /**
     * Clears any mocks that were defined and cleans
     * up application class configuration.
     *
     * @after
     * @return void
     */
    public function cleanupContainer(): void
    {
        $this->_appArgs = null;
        $this->_appClass = null;
        $this->containerServices = [];
    }
}

// phpcs:disable
class_alias(
    'Cake\Core\TestSuite\ContainerStubTrait',
    'Cake\TestSuite\ContainerStubTrait'
);
// phpcs:enable
