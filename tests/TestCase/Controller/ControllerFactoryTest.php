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

use Cake\Controller\ControllerFactory;
use Cake\Controller\Exception\InvalidParameterException;
use Cake\Core\Container;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
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
     * @var \Cake\Core\Container
     */
    protected $container;

    /**
     * Setup
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
     */
    public function testApplicationController(): void
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
     */
    public function testPrefixedAppController(): void
    {
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
    }

    /**
     * Test building a nested prefix app controller
     */
    public function testNestedPrefixedAppController(): void
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
     */
    public function testPluginController(): void
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
     */
    public function testVendorPluginController(): void
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
     */
    public function testPrefixedPluginController(): void
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

    public function testAbstractClassFailure(): void
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

    public function testInterfaceFailure(): void
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

    public function testMissingClassFailure(): void
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

    public function testSlashedControllerFailure(): void
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

    public function testAbsoluteReferenceFailure(): void
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
     * Test create() injecting dependencies on defined controllers.
     */
    public function testCreateWithContainerDependenciesNoController(): void
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
     * Test create() injecting dependencies on defined controllers.
     */
    public function testCreateWithContainerDependenciesWithController(): void
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'index',
            ],
        ]);
        $this->container->add(stdClass::class, json_decode('{"key":"value"}'));
        $this->container->add(ServerRequest::class, $request);
        $this->container->add(DependenciesController::class)
            ->addArgument(ServerRequest::class)
            ->addArgument(null)
            ->addArgument(null)
            ->addArgument(null)
            ->addArgument(null)
            ->addArgument(stdClass::class);

        $controller = $this->factory->create($request);
        $this->assertInstanceOf(DependenciesController::class, $controller);
        $this->assertSame($controller->inject, $this->container->get(stdClass::class));
    }

    /**
     * Test building controller name when passing no controller name
     */
    public function testGetControllerClassNoControllerName(): void
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
     */
    public function testInvokeAutoRender(): void
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
     */
    public function testInvokeAutoRenderFalse(): void
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
     */
    public function testStartupProcessAbort(): void
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
     */
    public function testShutdownProcessResponse(): void
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
     */
    public function testInvokeInjectOptionalParameterDefined(): void
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
     */
    public function testInvokeInjectParametersOptionalNotDefined(): void
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
     * Test invoke passing basic typed data from pass parameters.
     */
    public function testInvokeInjectParametersOptionalWithPassedParameters(): void
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
     * Test invoke() injecting dependencies that exist in passed params as objects.
     * The accepted types of `params.pass` was never enforced and userland code has
     * creative uses of this previously unspecified behavior.
     */
    public function testCreateWithContainerDependenciesWithObjectRouteParam(): void
    {
        $inject = new stdClass();
        $inject->id = uniqid();

        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredDep',
                'pass' => [$inject],
            ],
        ]);
        $controller = $this->factory->create($request);
        $response = $this->factory->invoke($controller);

        $data = json_decode((string)$response->getBody());
        $this->assertNotNull($data);
        $this->assertEquals($data->dep->id, $inject->id);
    }

    public function testCreateWithNonStringScalarRouteParam(): void
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/required_typed',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredTyped',
                'pass' => [1.1, 2, true, ['foo' => 'bar']],
            ],
        ]);
        $controller = $this->factory->create($request);
        $response = $this->factory->invoke($controller);

        $expected = ['one' => 1.1, 'two' => 2, 'three' => true, 'four' => ['foo' => 'bar']];
        $data = json_decode((string)$response->getBody(), true);
        $this->assertSame($expected, $data);
    }

    /**
     * Ensure that a controllers startup process can emit a response
     */
    public function testInvokeInjectParametersRequiredDefined(): void
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
     */
    public function testInvokeInjectParametersRequiredNotDefined(): void
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

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Failed to inject dependency from service container for parameter `dep` with type `stdClass` in action Dependencies::requiredDep()'
        );
        $this->factory->invoke($controller);
    }

    public function testInvokeInjectParametersRequiredMissingUntyped(): void
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

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Missing passed parameter for `one` in action Dependencies::requiredParam()');
        $this->factory->invoke($controller);
    }

    public function testInvokeInjectParametersRequiredUntyped(): void
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

    public function testInvokeInjectParametersRequiredWithPassedParameters(): void
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
     */
    public function testInvokeInjectPassedParametersVariadic(): void
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
     */
    public function testInvokeInjectPassedParametersSpread(): void
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
     */
    public function testInvokeInjectPassedParametersSpreadNoParams(): void
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
     */
    public function testInvokeOptionalStringParam(): void
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
     */
    public function testInvokeRequiredStringParam(): void
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

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Missing passed parameter for `str` in action Dependencies::requiredString()');
        $this->factory->invoke($controller);
    }

    /**
     * Test that coercing string to float, int and bool params
     */
    public function testInvokePassedParametersCoercion(): void
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredTyped',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredTyped',
                'pass' => ['1.0', '2', '0', '8,9'],
            ],
        ]);
        $controller = $this->factory->create($request);

        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody(), true);
        $this->assertSame(['one' => 1.0, 'two' => 2, 'three' => false, 'four' => ['8', '9']], $data);

        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredTyped',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredTyped',
                'pass' => ['1.0', '0', '0', ''],
            ],
        ]);
        $controller = $this->factory->create($request);

        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody(), true);
        $this->assertSame(['one' => 1.0, 'two' => 0, 'three' => false, 'four' => []], $data);

        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredTyped',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredTyped',
                'pass' => ['1.0', '-1', '0', ''],
            ],
        ]);
        $controller = $this->factory->create($request);

        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody(), true);
        $this->assertSame(['one' => 1.0, 'two' => -1, 'three' => false, 'four' => []], $data);
    }

    /**
     * Test that default values work for typed parameters
     */
    public function testInvokeOptionalTypedParam(): void
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/optionalTyped',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'optionalTyped',
                'pass' => ['1.0'],
            ],
        ]);
        $controller = $this->factory->create($request);

        $result = $this->factory->invoke($controller);
        $data = json_decode((string)$result->getBody(), true);

        $this->assertSame(['one' => 1.0, 'two' => 2, 'three' => true], $data);
    }

    /**
     * Test using invalid value for supported type
     */
    public function testInvokePassedParametersUnsupportedFloatCoercion(): void
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredTyped',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredTyped',
                'pass' => ['true', '2', '1'],
            ],
        ]);
        $controller = $this->factory->create($request);

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unable to coerce "true" to `float` for `one` in action Dependencies::requiredTyped()');
        $this->factory->invoke($controller);
    }

    /**
     * Test using invalid value for supported type
     */
    public function testInvokePassedParametersUnsupportedIntCoercion(): void
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredTyped',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredTyped',
                'pass' => ['1', '2.0', '1'],
            ],
        ]);
        $controller = $this->factory->create($request);

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unable to coerce "2.0" to `int` for `two` in action Dependencies::requiredTyped()');
        $this->factory->invoke($controller);
    }

    /**
     * Test using invalid value for supported type
     */
    public function testInvokePassedParametersUnsupportedBoolCoercion(): void
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/requiredTyped',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'requiredTyped',
                'pass' => ['1', '1', 'true'],
            ],
        ]);
        $controller = $this->factory->create($request);

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unable to coerce "true" to `bool` for `three` in action Dependencies::requiredTyped()');
        $this->factory->invoke($controller);
    }

    /**
     * Test using an unsupported type.
     */
    public function testInvokePassedParamUnsupportedType(): void
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/dependencies/unsupportedTyped',
            'params' => [
                'plugin' => null,
                'controller' => 'Dependencies',
                'action' => 'unsupportedTyped',
                'pass' => ['test'],
            ],
        ]);
        $controller = $this->factory->create($request);

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unable to coerce "test" to `iterable` for `one` in action Dependencies::unsupportedTyped()');
        $this->factory->invoke($controller);
    }

    public function testMiddleware(): void
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
        $this->factory->invoke($controller);

        $request = $controller->getRequest();
        $this->assertTrue($request->getAttribute('for-all'));
        $this->assertTrue($request->getAttribute('index-only'));
        $this->assertNull($request->getAttribute('all-except-index'));

        $request = new ServerRequest([
            'url' => 'posts/get',
            'params' => [
                'controller' => 'Posts',
                'action' => 'get',
                'pass' => [],
            ],
        ]);
        $controller = $this->factory->create($request);
        $this->factory->invoke($controller);

        $request = $controller->getRequest();
        $this->assertTrue($request->getAttribute('for-all'));
        $this->assertNull($request->getAttribute('index-only'));
        $this->assertTrue($request->getAttribute('all-except-index'));
    }
}
