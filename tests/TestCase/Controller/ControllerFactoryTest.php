<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use ArgumentCountError;
use Cake\Controller\ControllerFactory;
use Cake\Core\Container;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use stdClass;
use TestApp\Controller\DependenciesController;

/**
 * Test case for ControllerFactory.
 */
class ControllerFactoryTest extends TestCase
{
    /**
     * @var \Cake\Controller\ControllerFactory
     */
    protected $factory;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        $this->container = new Container();
        $this->factory = new ControllerFactory($this->container);
    }

    /**
     * Test building an application controller
     *
     * @return void
     */
    public function testApplicationController()
    {
        $request = new ServerRequest([
            'url' => 'cakes/index',
            'params' => [
                'controller' => 'Cakes',
                'action' => 'index',
            ],
        ]);
        $result = $this->factory->create($request);
        $this->assertInstanceOf('TestApp\Controller\CakesController', $result);
        $this->assertSame($request, $result->getRequest());
    }

    /**
     * Test building a prefixed app controller.
     *
     * @return void
     */
    public function testPrefixedAppControllerDeprecated()
    {
        $this->deprecated(function () {
            $request = new ServerRequest([
                'url' => 'admin/posts/index',
                'params' => [
                    'prefix' => 'Admin',
                    'controller' => 'Posts',
                    'action' => 'index',
                ],
            ]);
            $result = $this->factory->create($request);
            $this->assertInstanceOf(
                'TestApp\Controller\Admin\PostsController',
                $result
            );
            $this->assertSame($request, $result->getRequest());
        });
    }

    /**
     * Test building a nested prefix app controller
     *
     * @return void
     */
    public function testNestedPrefixedAppController()
    {
        $request = new ServerRequest([
            'url' => 'admin/sub/posts/index',
            'params' => [
                'prefix' => 'Admin/Sub',
                'controller' => 'Posts',
                'action' => 'index',
            ],
        ]);
        $result = $this->factory->create($request);
        $this->assertInstanceOf(
            'TestApp\Controller\Admin\Sub\PostsController',
            $result
        );
        $this->assertSame($request, $result->getRequest());
    }

    /**
     * Test building a plugin controller
     *
     * @return void
     */
    public function testPluginController()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin/test_plugin/index',
            'params' => [
                'plugin' => 'TestPlugin',
                'controller' => 'TestPlugin',
                'action' => 'index',
            ],
        ]);
        $result = $this->factory->create($request);
        $this->assertInstanceOf(
            'TestPlugin\Controller\TestPluginController',
            $result
        );
        $this->assertSame($request, $result->getRequest());
    }

    /**
     * Test building a vendored plugin controller.
     *
     * @return void
     */
    public function testVendorPluginController()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/ovens/index',
            'params' => [
                'plugin' => 'Company/TestPluginThree',
                'controller' => 'Ovens',
                'action' => 'index',
            ],
        ]);
        $result = $this->factory->create($request);
        $this->assertInstanceOf(
            'Company\TestPluginThree\Controller\OvensController',
            $result
        );
        $this->assertSame($request, $result->getRequest());
    }

    /**
     * Test building a prefixed plugin controller
     *
     * @return void
     */
    public function testPrefixedPluginController()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin/admin/comments',
            'params' => [
                'prefix' => 'Admin',
                'plugin' => 'TestPlugin',
                'controller' => 'Comments',
                'action' => 'index',
            ],
        ]);
        $result = $this->factory->create($request);
        $this->assertInstanceOf(
            'TestPlugin\Controller\Admin\CommentsController',
            $result
        );
        $this->assertSame($request, $result->getRequest());
    }

    /**
     * @return void
     */
    public function testAbstractClassFailure()
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class Abstract could not be found.');
        $request = new ServerRequest([
            'url' => 'abstract/index',
            'params' => [
                'controller' => 'Abstract',
                'action' => 'index',
            ],
        ]);
        $this->factory->create($request);
    }

    /**
     * @return void
     */
    public function testInterfaceFailure()
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class Interface could not be found.');
        $request = new ServerRequest([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'Interface',
                'action' => 'index',
            ],
        ]);
        $this->factory->create($request);
    }

    /**
     * @return void
     */
    public function testMissingClassFailure()
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class Invisible could not be found.');
        $request = new ServerRequest([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'Invisible',
                'action' => 'index',
            ],
        ]);
        $this->factory->create($request);
    }

    /**
     * @return void
     */
    public function testSlashedControllerFailure()
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class Admin/Posts could not be found.');
        $request = new ServerRequest([
            'url' => 'admin/posts/index',
            'params' => [
                'controller' => 'Admin/Posts',
                'action' => 'index',
            ],
        ]);
        $this->factory->create($request);
    }

    /**
     * @return void
     */
    public function testAbsoluteReferenceFailure()
    {
        $this->expectException(MissingControllerException::class);
        $this->expectExceptionMessage('Controller class TestApp\Controller\CakesController could not be found.');
        $request = new ServerRequest([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'TestApp\Controller\CakesController',
                'action' => 'index',
            ],
        ]);
        $this->factory->create($request);
    }

    /**
     * Test create() injecting dependcies on defined controllers.
     *
     * @return void
     */
    public function testCreateWithContainerDependenciesNoController()
    {
        $this->container->add(stdClass::class, json_decode('{"key":"value"}'));

        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'index',
            ],
        ]);
        $controller = $this->factory->create($request);
        $this->assertNull($controller->inject);
    }

    /**
     * Test create() injecting dependcies on defined controllers.
     *
     * @return void
     */
    public function testCreateWithContainerDependenciesWithController()
    {
        $this->container->add(stdClass::class, json_decode('{"key":"value"}'));
        $this->container->add(DependenciesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(null)
            ->addArgument(null)
            ->addArgument(null)
            ->addArgument(null)
            ->addArgument(stdClass::class);

        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'index',
            ],
        ]);
        $controller = $this->factory->create($request);
        $this->assertInstanceOf(DependenciesController::class, $controller);
        $this->assertSame($controller->inject, $this->container->get(stdClass::class));
    }

    /**
     * Test building controller name when passing no controller name
     *
     * @return void
     */
    public function testGetControllerClassNoControllerName()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/ovens/index',
            'params' => [
                'plugin' => 'Company/TestPluginThree',
                'controller' => 'Ovens',
                'action' => 'index',
            ],
        ]);
        $result = $this->factory->getControllerClass($request);
        $this->assertSame('Company\TestPluginThree\Controller\OvensController', $result);
    }

    /**
     * Test invoke with autorender
     *
     * @return void
     */
    public function testInvokeAutoRender()
    {
        $request = new ServerRequest([
            'url' => 'posts',
            'params' => [
                'controller' => 'Posts',
                'action' => 'index',
                'pass' => [],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertStringContainsString('posts index', (string)$result->getBody());
    }

    /**
     * Test dispatch with autorender=false
     *
     * @return void
     */
    public function testInvokeAutoRenderFalse()
    {
        $request = new ServerRequest([
            'url' => 'posts',
            'params' => [
                'controller' => 'Cakes',
                'action' => 'noRender',
                'pass' => [],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertStringContainsString('autoRender false body', (string)$result->getBody());
    }

    /**
     * Ensure that a controller's startup event can stop the request.
     *
     * @return void
     */
    public function testStartupProcessAbort()
    {
        $request = new ServerRequest([
            'url' => 'cakes/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'stop' => 'startup',
                'pass' => [],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);

        $this->assertSame('startup stop', (string)$result->getBody());
    }

    /**
     * Ensure that a controllers startup process can emit a response
     *
     * @return void
     */
    public function testShutdownProcessResponse()
    {
        $request = new ServerRequest([
            'url' => 'cakes/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'stop' => 'shutdown',
                'pass' => [],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);

        $this->assertSame('shutdown stop', (string)$result->getBody());
    }

    /**
     * Ensure that a controllers startup process can emit a response
     *
     * @return void
     */
    public function testInvokeInjectOptionalParameterDefined()
    {
        $this->container->add(stdClass::class, json_decode('{"key":"value"}'));
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/optionalDep',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'optionalDep',
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertNull($data->any);
        $this->assertNull($data->str);
        $this->assertSame('value', $data->dep->key);
    }

    /**
     * Ensure that a controllers startup process can emit a response
     *
     * @return void
     */
    public function testInvokeInjectParametersOptionalNotDefined()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'optionalDep',
            ],
        ]);
        $controller = $this->factory->create($request);

        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertNull($data->any);
        $this->assertNull($data->str);
        $this->assertNull($data->dep);
    }

    /**
     * Ensure that a controllers startup process can emit a response
     *
     * @return void
     */
    public function testInvokeInjectParametersOptionalWithPassedParameters()
    {
        $this->container->add(stdClass::class, json_decode('{"key":"value"}'));
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/optionalDep',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'optionalDep',
                'pass' => ['one', 'two'],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertSame($data->any, 'one');
        $this->assertSame($data->str, 'two');
        $this->assertSame('value', $data->dep->key);
    }

    /**
     * Ensure that a controllers startup process can emit a response
     *
     * @return void
     */
    public function testInvokeInjectParametersRequiredDefined()
    {
        $this->container->add(stdClass::class, json_decode('{"key":"value"}'));
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredDep',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredDep',
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertNull($data->any);
        $this->assertNull($data->str);
        $this->assertSame('value', $data->dep->key);
    }

    /**
     * Ensure that a controllers startup process can emit a response
     *
     * @return void
     */
    public function testInvokeInjectParametersRequiredNotDefined()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredDep',
            ],
        ]);
        $controller = $this->factory->create($request);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not resolve action argument `dep`');
        $this->factory->invoke($controller);
    }

    public function testInvokeInjectParametersRequiredMissingUntyped()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredParam',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredParam',
            ],
        ]);
        $controller = $this->factory->create($request);

        $this->expectException(ArgumentCountError::class);
        $this->expectExceptionMessage('Too few arguments');
        $this->factory->invoke($controller);
    }

    public function testInvokeInjectParametersRequiredUntyped()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredParam',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredParam',
                'pass' => ['one'],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertSame($data->one, 'one');
    }

    public function testInvokeInjectParametersRequiredWithPassedParameters()
    {
        $this->container->add(stdClass::class, json_decode('{"key":"value"}'));
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredDep',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredDep',
                'pass' => ['one', 'two'],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertSame($data->any, 'one');
        $this->assertSame($data->str, 'two');
        $this->assertSame('value', $data->dep->key);
    }

    /**
     * Test that routing parameters are passed into variadic controller functions
     *
     * @return void
     */
    public function testInvokeInjectPassedParametersVariadic()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/variadic',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'variadic',
                'pass' => ['one', 'two'],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertSame(['one', 'two'], $data->args);
    }

    /**
     * Test that routing parameters are passed into controller action using spread operator
     *
     * @return void
     */
    public function testInvokeInjectPassedParametersSpread()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/spread',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'spread',
                'pass' => ['one', 'two'],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertSame(['one', 'two'], $data->args);
    }

    /**
     * Test that routing parameters are passed into controller action using spread operator
     *
     * @return void
     */
    public function testInvokeInjectPassedParametersSpreadNoParams()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/spread',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'spread',
                'pass' => [],
            ],
        ]);
        $controller = $this->factory->create($request);
        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertSame([], $data->args);
    }

    /**
     * Test that default parameters work for controller methods
     *
     * @return void
     */
    public function testInvokeOptionalStringParam()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/optionalString',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'optionalString',
            ],
        ]);
        $controller = $this->factory->create($request);

        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody());

        $this->assertNotNull($data);
        $this->assertSame('default val', $data->str);
    }

    /**
     * Test that required strings a default value.
     *
     * @return void
     */
    public function testInvokeRequiredStringParam()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredString',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredString',
            ],
        ]);
        $controller = $this->factory->create($request);

        $this->expectException(ArgumentCountError::class);
        $this->expectExceptionMessage('Too few arguments');
        $this->factory->invoke($controller);
    }
}
