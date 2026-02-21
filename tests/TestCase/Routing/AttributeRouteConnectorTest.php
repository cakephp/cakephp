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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\AttributeResolver\AttributeCollection;
use Cake\AttributeResolver\AttributeResolver;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\AttributeResolver\ValueObject\AttributeTarget;
use Cake\Cache\Cache;
use Cake\Http\ServerRequest;
use Cake\Routing\Attribute\Extensions;
use Cake\Routing\Attribute\Middleware;
use Cake\Routing\Attribute\Prefix;
use Cake\Routing\Attribute\Resource;
use Cake\Routing\Attribute\Route;
use Cake\Routing\Attribute\RouteClass;
use Cake\Routing\Attribute\Scope;
use Cake\Routing\AttributeRouteConnector;
use Cake\Routing\Route\InflectedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\TestSuite\TestCase;
use ReflectionProperty;

/**
 * Tests for attribute route parsing and connection connector.
 */
class AttributeRouteConnectorTest extends TestCase
{
    /**
     * @var \Cake\Routing\RouteCollection
     */
    protected RouteCollection $collection;

    /**
     * Initializes routing collection and attribute resolver configuration.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new RouteCollection();

        AttributeResolver::setConfig('default', [
            'paths' => ['Controller/*Controller.php', 'Controller/**/*Controller.php'],
            'basePath' => APP,
            'excludePaths' => [],
            'excludeAttributes' => [],
            'cache' => '_cake_attributes_',
        ]);
        Cache::clear('_cake_attributes_');
    }

    /**
     * Clears resolver state between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        AttributeResolver::drop('default');
        $reflection = new ReflectionProperty(AttributeResolver::class, 'collections');
        $reflection->setValue(null, []);
        Cache::clear('_cake_attributes_');
        parent::tearDown();
    }

    /**
     * Tests that helper connects parent class method routes.
     *
     * @return void
     */
    public function testConnectInheritsParentMethodRoutes(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);
        $helper->connect();

        $request = new ServerRequest([
            'url' => '/base/attr/parent',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);
        $result = $this->collection->parseRequest($request);

        $this->assertSame('AttributeRouting', $result['controller']);
        $this->assertSame('parentRoute', $result['action']);
    }

    /**
     * Tests that helper derives prefix defaults from controller namespaces.
     *
     * @return void
     */
    public function testConnectDerivesPrefixFromControllerNamespace(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);
        $helper->connect();

        $request = new ServerRequest([
            'url' => '/admin/dashboard',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);
        $result = $this->collection->parseRequest($request);

        $this->assertSame('AttributeRouting', $result['controller']);
        $this->assertSame('dashboard', $result['action']);
        $this->assertSame('Admin', $result['prefix']);
    }

    /**
     * Tests that method-level extensions override controller-level extensions.
     *
     * @return void
     */
    public function testConnectUsesMethodExtensionsOverride(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);
        $helper->connect();

        $request = new ServerRequest([
            'url' => '/base/attr/feed.xml',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);
        $result = $this->collection->parseRequest($request);

        $this->assertSame('feed', $result['action']);
        $this->assertSame('xml', $result['_ext']);
    }

    /**
     * Tests branch handling for class existence and controller metadata checks.
     *
     * @return void
     */
    public function testConnectSkipsNonExistingAndNonControllerClasses(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);

        $attributes = [
            new AttributeInfo(
                className: 'Missing\\Controller\\GhostController',
                attributeName: Route::class,
                arguments: ['/ghost', 'ghost'],
                filePath: __FILE__,
                lineNumber: 1,
                target: new AttributeTarget(AttributeTargetType::METHOD, 'index', 'Missing\\Controller\\GhostController'),
            ),
            new AttributeInfo(
                className: 'Cake\\Routing\\RouteBuilder',
                attributeName: Route::class,
                arguments: ['/builder', 'builder'],
                filePath: __FILE__,
                lineNumber: 2,
                target: new AttributeTarget(AttributeTargetType::METHOD, 'path', 'Cake\\Routing\\RouteBuilder'),
            ),
        ];

        $this->injectResolverCollection('injected-skip', new AttributeCollection($attributes));

        $helper->connect('injected-skip');

        $this->assertCount(0, $this->collection->routes());
    }

    /**
     * Tests branch handling for abstract controller classes.
     *
     * @return void
     */
    public function testConnectSkipsAbstractControllerClasses(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);

        $className = 'TestApp\\Controller\\AttributeRoutingBaseController';
        $attributes = [
            new AttributeInfo(
                className: $className,
                attributeName: Scope::class,
                arguments: ['/base', 'base:', [], [], null],
                filePath: __FILE__,
                lineNumber: 3,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingBaseController', $className, true),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Route::class,
                arguments: ['/parent', 'parent', ['GET']],
                filePath: __FILE__,
                lineNumber: 4,
                target: new AttributeTarget(AttributeTargetType::METHOD, 'parentRoute', $className, true),
            ),
        ];

        $this->injectResolverCollection('injected-abstract-skip', new AttributeCollection($attributes));

        $helper->connect('injected-abstract-skip');

        $this->assertCount(0, $this->collection->routes());
    }

    /**
     * Tests helper support for prefix, route class, middleware and persist/host options.
     *
     * @return void
     */
    public function testConnectAppliesPrefixRouteClassMiddlewareAndPersistOptions(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);

        $className = 'TestApp\\Controller\\AttributeRoutingController';
        $attributes = [
            new AttributeInfo(
                className: $className,
                attributeName: Prefix::class,
                arguments: ['Admin', '/custom-admin'],
                filePath: __FILE__,
                lineNumber: 10,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: RouteClass::class,
                arguments: [InflectedRoute::class],
                filePath: __FILE__,
                lineNumber: 11,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Middleware::class,
                arguments: ['sample'],
                filePath: __FILE__,
                lineNumber: 12,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Route::class,
                arguments: [
                    '/injected',
                    'injected',
                    ['PUT', 'PATCH'],
                    [],
                    [],
                    [],
                    ['lang'],
                    'api.example.com',
                    null,
                ],
                filePath: __FILE__,
                lineNumber: 13,
                target: new AttributeTarget(AttributeTargetType::METHOD, 'index', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Middleware::class,
                arguments: ['dumb'],
                filePath: __FILE__,
                lineNumber: 14,
                target: new AttributeTarget(AttributeTargetType::METHOD, 'index', $className),
            ),
        ];

        $this->injectResolverCollection('injected-options', new AttributeCollection($attributes));

        $helper->connect('injected-options');

        $request = new ServerRequest([
            'url' => '/custom-admin/injected',
            'environment' => ['REQUEST_METHOD' => 'PUT', 'HTTP_HOST' => 'api.example.com'],
        ]);
        $result = $this->collection->parseRequest($request);

        $this->assertSame('AttributeRouting', $result['controller']);
        $this->assertSame('index', $result['action']);
        $this->assertSame('Admin', $result['prefix']);
        $this->assertSame('/custom-admin/injected', $result['_matchedRoute']);
        $this->assertSame(['sample', 'dumb'], $result['_middleware']);

        $route = $this->collection->routes()[0];
        $this->assertInstanceOf(InflectedRoute::class, $route);
        $this->assertSame(['lang'], $route->options['persist']);
        $this->assertSame('api.example.com', $route->options['_host']);
    }

    /**
     * Tests that pass parameters default to placeholder order with args-by-name metadata.
     *
     * @return void
     */
    public function testConnectUsesPlaceholderNamesAsDefaultPassList(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);

        $className = 'TestApp\\Controller\\AttributeRoutingController';
        $baseClassName = 'TestApp\\Controller\\AttributeRoutingBaseController';
        $attributes = [
            new AttributeInfo(
                className: $baseClassName,
                attributeName: Scope::class,
                arguments: ['/base', 'base:', [], [], null],
                filePath: __FILE__,
                lineNumber: 13,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingBaseController', $baseClassName),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Scope::class,
                arguments: ['/attr', 'attr:', [], [], null],
                filePath: __FILE__,
                lineNumber: 14,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Route::class,
                arguments: ['/inferred/{id}/{slug}', 'inferred', ['GET'], [], [], null, [], null, null],
                filePath: __FILE__,
                lineNumber: 15,
                target: new AttributeTarget(AttributeTargetType::METHOD, 'reorder', $className),
            ),
        ];

        $this->injectResolverCollection('injected-inferred-pass', new AttributeCollection($attributes));

        $helper->connect('injected-inferred-pass');

        $route = $this->collection->routes()[0];
        $this->assertSame(['id', 'slug'], $route->options['pass']);
        $this->assertSame(['id' => 0, 'slug' => 1], $route->defaults['_argsByName']);

        $result = $this->collection->parseRequest(new ServerRequest([
            'url' => '/base/attr/inferred/10/first-post',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]));

        $this->assertSame('AttributeRouting', $result['controller']);
        $this->assertSame('reorder', $result['action']);
        $this->assertSame(['10', 'first-post'], $result['pass']);
    }

    /**
     * Tests that explicit empty pass values disable inferred pass behavior.
     *
     * @return void
     */
    public function testConnectHonorsExplicitEmptyPassList(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);

        $className = 'TestApp\\Controller\\AttributeRoutingController';
        $baseClassName = 'TestApp\\Controller\\AttributeRoutingBaseController';
        $attributes = [
            new AttributeInfo(
                className: $baseClassName,
                attributeName: Scope::class,
                arguments: ['/base', 'base:', [], [], null],
                filePath: __FILE__,
                lineNumber: 13,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingBaseController', $baseClassName),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Scope::class,
                arguments: ['/attr', 'attr:', [], [], null],
                filePath: __FILE__,
                lineNumber: 14,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Route::class,
                arguments: ['/no-pass/{id}/{slug}', 'no-pass', ['GET'], [], [], [], [], null, null],
                filePath: __FILE__,
                lineNumber: 15,
                target: new AttributeTarget(AttributeTargetType::METHOD, 'reorder', $className),
            ),
        ];

        $this->injectResolverCollection('injected-empty-pass', new AttributeCollection($attributes));

        $helper->connect('injected-empty-pass');

        $route = $this->collection->routes()[0];
        $this->assertSame([], $route->options['pass']);

        $result = $this->collection->parseRequest(new ServerRequest([
            'url' => '/base/attr/no-pass/10/first-post',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]));

        $this->assertSame('AttributeRouting', $result['controller']);
        $this->assertSame('reorder', $result['action']);
        $this->assertSame([], $result['pass']);
    }

    /**
     * Tests that resource attributes generate REST routes with shared class options.
     *
     * @return void
     */
    public function testConnectBuildsResourceRoutesFromAttribute(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);

        $className = 'TestApp\\Controller\\AttributeRoutingController';
        $attributes = [
            new AttributeInfo(
                className: $className,
                attributeName: Middleware::class,
                arguments: ['sample'],
                filePath: __FILE__,
                lineNumber: 20,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Resource::class,
                arguments: ['articles', ['index', 'view'], [], [], null, '\\d+', 'dasherize', []],
                filePath: __FILE__,
                lineNumber: 21,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
        ];

        $this->injectResolverCollection('injected-resource', new AttributeCollection($attributes));

        $helper->connect('injected-resource');

        $indexResult = $this->collection->parseRequest(new ServerRequest([
            'url' => '/articles',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]));
        $this->assertSame('AttributeRouting', $indexResult['controller']);
        $this->assertSame('index', $indexResult['action']);
        $this->assertSame(['sample'], $indexResult['_middleware']);

        $viewResult = $this->collection->parseRequest(new ServerRequest([
            'url' => '/articles/123',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]));
        $this->assertSame('view', $viewResult['action']);
        $this->assertSame('123', $viewResult['id']);
    }

    /**
     * Tests that resource routes keep plugin defaults when discovered from plugin classes.
     *
     * @return void
     */
    public function testConnectBuildsPluginResourceRoutes(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);

        $className = 'TestPlugin\\Controller\\AttributeTestController';
        $attributes = [
            new AttributeInfo(
                className: $className,
                attributeName: Resource::class,
                arguments: [null, ['index'], [], [], null, '', 'dasherize', []],
                filePath: __FILE__,
                lineNumber: 30,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeTestController', $className),
                pluginName: 'TestPlugin',
            ),
        ];

        $this->injectResolverCollection('injected-plugin-resource', new AttributeCollection($attributes));

        $helper->connect('injected-plugin-resource');

        $result = $this->collection->parseRequest(new ServerRequest([
            'url' => '/attribute-test',
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]));

        $this->assertSame('TestPlugin', $result['plugin']);
        $this->assertSame('AttributeTest', $result['controller']);
        $this->assertSame('index', $result['action']);
    }

    /**
     * Tests that resource attributes apply prefix, scope host and class extensions.
     *
     * @return void
     */
    public function testConnectBuildsResourceRoutesWithPrefixHostAndExtensions(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new AttributeRouteConnector($routes);

        $className = 'TestApp\\Controller\\AttributeRoutingController';
        $attributes = [
            new AttributeInfo(
                className: $className,
                attributeName: Prefix::class,
                arguments: ['Admin', '/custom-admin'],
                filePath: __FILE__,
                lineNumber: 40,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Scope::class,
                arguments: ['', '', [], [], 'api.example.com'],
                filePath: __FILE__,
                lineNumber: 41,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Extensions::class,
                arguments: [['json']],
                filePath: __FILE__,
                lineNumber: 42,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
            new AttributeInfo(
                className: $className,
                attributeName: Resource::class,
                arguments: [null, ['index'], [], [], null, '', 'dasherize', []],
                filePath: __FILE__,
                lineNumber: 43,
                target: new AttributeTarget(AttributeTargetType::CLASS_TYPE, 'AttributeRoutingController', $className),
            ),
        ];

        $this->injectResolverCollection('injected-resource-scope', new AttributeCollection($attributes));

        $helper->connect('injected-resource-scope');

        $result = $this->collection->parseRequest(new ServerRequest([
            'url' => '/custom-admin/attribute-routing.json',
            'environment' => ['REQUEST_METHOD' => 'GET', 'HTTP_HOST' => 'api.example.com'],
        ]));

        $this->assertSame('AttributeRouting', $result['controller']);
        $this->assertSame('index', $result['action']);
        $this->assertSame('Admin', $result['prefix']);
        $this->assertSame('json', $result['_ext']);
    }

    /**
     * Tests metadata extraction for controller and non-controller class names.
     *
     * @return void
     */
    public function testExtractControllerMetadata(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new class ($routes) extends AttributeRouteConnector {
            /**
             * @param string $className Controller class name.
             * @param string|null $pluginName Plugin name.
             * @return array{plugin: string|null, controller: string, prefix: string|null, prefixPath: string}|null
             */
            public function callExtractControllerMetadata(string $className, ?string $pluginName): ?array
            {
                return $this->extractControllerMetadata($className, $pluginName);
            }
        };

        $this->assertNull($helper->callExtractControllerMetadata('App\\Model\\Table\\UsersTable', null));
        $this->assertNull($helper->callExtractControllerMetadata('App\\Controller\\UsersTable', null));

        $metadata = $helper->callExtractControllerMetadata('TestApp\\Controller\\Admin\\AttributeRoutingController', null);
        $this->assertSame('AttributeRouting', $metadata['controller']);
        $this->assertSame('Admin', $metadata['prefix']);
        $this->assertSame('/admin', $metadata['prefixPath']);
    }

    /**
     * Tests connect controller class branch when no class attributes exist.
     *
     * @return void
     */
    public function testConnectControllerClassWithoutAttributes(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new class ($routes) extends AttributeRouteConnector {
            /**
             * @param string $className Controller class name.
             * @param array<string, array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>> $classAttributes Attributes grouped by class.
             * @return void
             */
            public function callConnectControllerClass(string $className, array $classAttributes): void
            {
                $this->connectControllerClass($className, $classAttributes);
            }
        };

        $helper->callConnectControllerClass('TestApp\\Controller\\AttributeRoutingController', []);

        $this->assertCount(0, $this->collection->routes());
    }

    /**
     * Tests method grouping skips magic names and empty declaring class metadata.
     *
     * @return void
     */
    public function testGetMethodAttributeGroupsSkipsMagicAndEmptyDeclaringClass(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new class ($routes) extends AttributeRouteConnector {
            /**
             * @param string $className Controller class name.
             * @param list<string> $hierarchy Parent-to-child class hierarchy.
             * @param array<string, array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>> $classAttributes Attributes grouped by class.
             * @return array<int, array{methodName: string, infos: array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>}>
             */
            public function callGetMethodAttributeGroups(string $className, array $hierarchy, array $classAttributes): array
            {
                return $this->getMethodAttributeGroups($className, $hierarchy, $classAttributes);
            }
        };

        $className = 'TestApp\\Controller\\AttributeRoutingController';
        $groups = $helper->callGetMethodAttributeGroups($className, [$className], [
            $className => [
                new AttributeInfo(
                    className: $className,
                    attributeName: Route::class,
                    arguments: ['/magic', 'magic'],
                    filePath: __FILE__,
                    lineNumber: 1,
                    target: new AttributeTarget(AttributeTargetType::METHOD, '__invoke', $className),
                ),
                new AttributeInfo(
                    className: $className,
                    attributeName: Route::class,
                    arguments: ['/empty', 'empty'],
                    filePath: __FILE__,
                    lineNumber: 2,
                    target: new AttributeTarget(AttributeTargetType::METHOD, 'index', ''),
                ),
            ],
        ]);

        $this->assertSame([], $groups);
    }

    /**
     * Tests args-by-name map includes explicit string keys and values.
     *
     * @return void
     */
    public function testBuildArgsByNameMapWithNamedKeys(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new class ($routes) extends AttributeRouteConnector {
            /**
             * @param array<int|string, string> $pass Route pass definitions.
             * @return array<string, int>
             */
            public function callBuildArgsByNameMap(array $pass): array
            {
                return $this->buildArgsByNameMap($pass);
            }
        };

        $result = $helper->callBuildArgsByNameMap(['slug' => 'slug', 'id']);

        $this->assertSame(['slug' => 0, 'id' => 1], $result);
    }

    /**
     * Tests path normalization behavior.
     *
     * @return void
     */
    public function testNormalizePath(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new class ($routes) extends AttributeRouteConnector {
            /**
             * @param string $path Raw route path.
             * @return string
             */
            public function callNormalizePath(string $path): string
            {
                return $this->normalizePath($path);
            }
        };

        $this->assertSame('/', $helper->callNormalizePath(''));
        $this->assertSame('/abc', $helper->callNormalizePath('abc'));
        $this->assertSame('/a/b', $helper->callNormalizePath('/a//b'));
        $this->assertSame('/a/b', $helper->callNormalizePath('///a///b'));
    }

    /**
     * Tests extraction of special route defaults into options.
     *
     * @return void
     */
    public function testExtractSpecialDefaults(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $helper = new class ($routes) extends AttributeRouteConnector {
            /**
             * @param array<string, mixed> $defaults Route defaults.
             * @param array<string, mixed> $options Route options.
             * @return array{defaults: array<string, mixed>, options: array<string, mixed>}
             */
            public function callExtractSpecialDefaults(array $defaults, array $options): array
            {
                $defaults = $this->extractSpecialDefaults($defaults, $options);

                return compact('defaults', 'options');
            }
        };

        $result = $helper->callExtractSpecialDefaults(
            ['action' => 'index', '_host' => 'example.com', '_https' => true, '_scheme' => 'https'],
            [],
        );

        $this->assertSame(['action' => 'index'], $result['defaults']);
        $this->assertSame('example.com', $result['options']['_host']);
        $this->assertTrue($result['options']['_https']);
        $this->assertSame('https', $result['options']['_scheme']);
    }

    /**
     * Injects an in-memory AttributeResolver collection for targeted helper tests.
     *
     * @param string $name Resolver collection name.
     * @param \Cake\AttributeResolver\AttributeCollection $collection Attribute collection.
     * @return void
     */
    protected function injectResolverCollection(string $name, AttributeCollection $collection): void
    {
        $reflection = new ReflectionProperty(AttributeResolver::class, 'collections');
        /** @var array<string, \Cake\AttributeResolver\AttributeCollection> $collections */
        $collections = $reflection->getValue();
        $collections[$name] = $collection;
        $reflection->setValue(null, $collections);
    }
}
