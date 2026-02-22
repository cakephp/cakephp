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
namespace Cake\Routing;

use Cake\AttributeResolver\AttributeResolver;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\Enum\MethodVisibility;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\Routing\Attribute\Delete;
use Cake\Routing\Attribute\Extensions;
use Cake\Routing\Attribute\Get;
use Cake\Routing\Attribute\Head;
use Cake\Routing\Attribute\Middleware;
use Cake\Routing\Attribute\Options;
use Cake\Routing\Attribute\Patch;
use Cake\Routing\Attribute\Post;
use Cake\Routing\Attribute\Prefix;
use Cake\Routing\Attribute\Put;
use Cake\Routing\Attribute\Resource;
use Cake\Routing\Attribute\Route as RouteAttribute;
use Cake\Routing\Attribute\RouteClass as RouteClassAttribute;
use Cake\Routing\Attribute\Scope;
use Cake\Utility\Inflector;

/**
 * Connector class that resolves and connects routes declared via PHP attributes.
 */
class AttributeRouteConnector
{
    /**
     * Supported routing attributes recognized by the resolver.
     *
     * @var list<class-string>
     */
    protected const array SUPPORTED_ATTRIBUTE_NAMES = [
        RouteAttribute::class,
        Get::class,
        Post::class,
        Put::class,
        Patch::class,
        Delete::class,
        Options::class,
        Head::class,
        Scope::class,
        Prefix::class,
        RouteClassAttribute::class,
        Middleware::class,
        Extensions::class,
        Resource::class,
    ];

    /**
     * @param \Cake\Routing\RouteBuilder $routeBuilder Route builder instance.
     */
    public function __construct(protected readonly RouteBuilder $routeBuilder)
    {
    }

    /**
     * Resolves attribute routes from the configured attribute resolver and connects them.
     *
     * @param string $config Attribute resolver config name.
     * @return void
     */
    public function connect(string $config = 'default'): void
    {
        $classAttributes = $this->groupAttributesByClass($config);
        $classNames = array_keys($classAttributes);
        sort($classNames);

        foreach ($classNames as $className) {
            $this->connectControllerClass($className, $classAttributes);
        }
    }

    /**
     * Loads attribute metadata and groups it by class name.
     *
     * @param string $config Attribute resolver config name.
     * @return array<string, array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>>
     */
    protected function groupAttributesByClass(string $config): array
    {
        $collection = AttributeResolver::collection($config)->withAttribute(static::SUPPORTED_ATTRIBUTE_NAMES);
        /** @var array<string, array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>> $classAttributes */
        $classAttributes = [];
        foreach ($collection as $attributeInfo) {
            $classAttributes[$attributeInfo->className][] = $attributeInfo;
        }

        foreach ($classAttributes as &$infos) {
            usort($infos, fn(AttributeInfo $a, AttributeInfo $b): int => $a->lineNumber <=> $b->lineNumber);
        }
        unset($infos);

        return $classAttributes;
    }

    /**
     * Connects all routes for a single controller class.
     *
     * @param string $className Controller class name.
     * @param array<string, array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>> $classAttributes Attributes grouped by class.
     * @return void
     */
    protected function connectControllerClass(string $className, array $classAttributes): void
    {
        if (!class_exists($className)) {
            return;
        }
        if (!isset($classAttributes[$className][0])) {
            return;
        }
        $controllerTarget = $classAttributes[$className][0]->target;
        if (!$controllerTarget->isInstantiableDeclaringType()) {
            return;
        }

        $classMetadata = $this->extractControllerMetadata($className, $classAttributes[$className][0]->pluginName);
        if ($classMetadata === null) {
            return;
        }

        $hierarchy = array_reverse(array_values(class_parents($className)));
        $hierarchy[] = $className;

        $classState = $this->buildClassRouteState($className, $hierarchy, $classAttributes, $classMetadata);
        $this->connectResourceRoutes($classMetadata, $classState);
        $this->connectMethodRoutes($className, $hierarchy, $classAttributes, $classMetadata, $classState);
    }

    /**
     * Builds route state aggregated from class-level attributes across inheritance.
     *
     * @param string $className Target controller class name.
     * @param list<string> $hierarchy Parent-to-child class hierarchy.
     * @param array<string, array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>> $classAttributes Attributes grouped by class.
     * @param array{plugin: string|null, controller: string, prefix: string|null, prefixPath: string} $classMetadata Controller metadata.
     * @return array{
     *     scopePath: string,
     *     scopeNamePrefix: string,
     *     scopeDefaults: array<string, mixed>,
     *     scopePatterns: array<string, mixed>,
     *     scopeHost: string|null,
     *     routeClass: string,
     *     classMiddleware: array<int, string>,
     *     classExtensions: array<int, string>,
     *     resourceAttributes: array<int, \Cake\Routing\Attribute\Resource>,
     *     prefixName: string|null,
     *     prefixPath: string
     * }
     */
    protected function buildClassRouteState(
        string $className,
        array $hierarchy,
        array $classAttributes,
        array $classMetadata,
    ): array {
        $state = [
            'scopePath' => '',
            'scopeNamePrefix' => '',
            'scopeDefaults' => [],
            'scopePatterns' => [],
            'scopeHost' => null,
            'routeClass' => $this->routeBuilder->getRouteClass(),
            'classMiddleware' => [],
            'classExtensions' => array_values($this->routeBuilder->getExtensions()),
            'resourceAttributes' => [],
            'prefixName' => $classMetadata['prefix'],
            'prefixPath' => $classMetadata['prefixPath'],
        ];

        foreach ($hierarchy as $hierarchyClass) {
            if (!isset($classAttributes[$hierarchyClass])) {
                continue;
            }
            $infos = $classAttributes[$hierarchyClass];

            foreach ($infos as $info) {
                if ($info->target->type !== AttributeTargetType::CLASS_TYPE) {
                    continue;
                }
                $this->applyClassAttributeState($className, $hierarchyClass, $info, $state);
            }
        }

        return $state;
    }

    /**
     * Applies one class-level attribute instance into the aggregated class route state.
     *
     * @param string $className Target controller class name.
     * @param string $hierarchyClass Class currently being processed.
     * @param \Cake\AttributeResolver\ValueObject\AttributeInfo $info Attribute metadata.
     * @param array{
     *     scopePath: string,
     *     scopeNamePrefix: string,
     *     scopeDefaults: array<string, mixed>,
     *     scopePatterns: array<string, mixed>,
     *     scopeHost: string|null,
     *     routeClass: string,
     *     classMiddleware: array<int, string>,
     *     classExtensions: array<int, string>,
     *     resourceAttributes: array<int, \Cake\Routing\Attribute\Resource>,
     *     prefixName: string|null,
     *     prefixPath: string
     * } $state Mutable class route state.
     * @return void
     */
    protected function applyClassAttributeState(
        string $className,
        string $hierarchyClass,
        AttributeInfo $info,
        array &$state,
    ): void {
        $instance = $info->getInstance();
        if ($instance instanceof Prefix && $hierarchyClass === $className) {
            $state['prefixName'] = $instance->name;
            $state['prefixPath'] = $instance->path ?? $state['prefixPath'];

            return;
        }
        if ($instance instanceof Scope) {
            $state['scopePath'] .= $instance->path;
            $state['scopeNamePrefix'] .= $instance->namePrefix;
            $state['scopeDefaults'] = array_merge($state['scopeDefaults'], $instance->defaults);
            $state['scopePatterns'] = array_merge($state['scopePatterns'], $instance->patterns);
            $state['scopeHost'] = $instance->host ?? $state['scopeHost'];

            return;
        }
        if ($instance instanceof RouteClassAttribute) {
            $state['routeClass'] = $instance->className;

            return;
        }
        if ($instance instanceof Middleware) {
            $state['classMiddleware'] = $this->mergeUniqueStrings($state['classMiddleware'], $instance->names);

            return;
        }
        if ($instance instanceof Extensions) {
            $state['classExtensions'] = array_values($instance->extensions);

            return;
        }
        if ($instance instanceof Resource) {
            $state['resourceAttributes'][] = $instance;
        }
    }

    /**
     * Connects REST resource routes for class-level resource attributes.
     *
     * @param array{plugin: string|null, controller: string, prefix: string|null, prefixPath: string} $classMetadata Controller metadata.
     * @param array{
     *     scopePath: string,
     *     scopeNamePrefix: string,
     *     scopeDefaults: array<string, mixed>,
     *     scopePatterns: array<string, mixed>,
     *     scopeHost: string|null,
     *     routeClass: string,
     *     classMiddleware: array<int, string>,
     *     classExtensions: array<int, string>,
     *     resourceAttributes: array<int, \Cake\Routing\Attribute\Resource>,
     *     prefixName: string|null,
     *     prefixPath: string
     * } $classState Class route state.
     * @return void
     */
    protected function connectResourceRoutes(array $classMetadata, array $classState): void
    {
        foreach ($classState['resourceAttributes'] as $resourceAttribute) {
            $resourcePath = $this->normalizePath($classState['prefixPath'] . $classState['scopePath']);

            $params = $classState['scopeDefaults'];
            if ($classMetadata['plugin'] !== null) {
                $params['plugin'] = $classMetadata['plugin'];
            }
            if ($classState['prefixName'] !== null && $classState['prefixName'] !== '') {
                $params['prefix'] = $classState['prefixName'];
            }

            $connectOptions = array_merge($classState['scopePatterns'], $resourceAttribute->connectOptions);
            if ($classState['scopeHost'] !== null && !isset($connectOptions['_host'])) {
                $connectOptions['_host'] = $classState['scopeHost'];
            }
            if ($classState['routeClass'] !== '' && !isset($connectOptions['routeClass'])) {
                $connectOptions['routeClass'] = $classState['routeClass'];
            }
            $resourceMiddleware = $this->mergeUniqueStrings(
                $this->routeBuilder->getMiddleware(),
                $classState['classMiddleware'],
            );
            if ($resourceMiddleware !== [] && !isset($connectOptions['_middleware'])) {
                $connectOptions['_middleware'] = $resourceMiddleware;
            }
            $params = $this->extractSpecialDefaults($params, $connectOptions);

            $resourceOptions = [
                'connectOptions' => $connectOptions,
                'only' => $resourceAttribute->only,
                'actions' => $resourceAttribute->actions,
                'map' => $resourceAttribute->map,
                'prefix' => $resourceAttribute->prefix,
                'inflect' => $resourceAttribute->inflect,
            ];
            if ($resourceAttribute->path !== null) {
                $resourceOptions['path'] = $resourceAttribute->path;
            }
            if ($resourceAttribute->id !== '') {
                $resourceOptions['id'] = $resourceAttribute->id;
            }
            if ($classState['classExtensions'] !== []) {
                $resourceOptions['_ext'] = $classState['classExtensions'];
            }

            $this->routeBuilder->scope(
                $resourcePath,
                function (RouteBuilder $routes) use ($classMetadata, $resourceOptions): void {
                    $routes->resources($classMetadata['controller'], null, $resourceOptions);
                },
                $params,
            );
        }
    }

    /**
     * Connects action routes for all public controller methods with route attributes.
     *
     * @param string $className Controller class name.
     * @param list<string> $hierarchy Parent-to-child class hierarchy.
     * @param array<string, array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>> $classAttributes Attributes grouped by class.
     * @param array{plugin: string|null, controller: string, prefix: string|null, prefixPath: string} $classMetadata Controller metadata.
     * @param array{
     *     scopePath: string,
     *     scopeNamePrefix: string,
     *     scopeDefaults: array<string, mixed>,
     *     scopePatterns: array<string, mixed>,
     *     scopeHost: string|null,
     *     routeClass: string,
     *     classMiddleware: array<int, string>,
     *     classExtensions: array<int, string>,
     *     resourceAttributes: array<int, \Cake\Routing\Attribute\Resource>,
     *     prefixName: string|null,
     *     prefixPath: string
     * } $classState Class route state.
     * @return void
     */
    protected function connectMethodRoutes(
        string $className,
        array $hierarchy,
        array $classAttributes,
        array $classMetadata,
        array $classState,
    ): void {
        foreach ($this->getMethodAttributeGroups($className, $hierarchy, $classAttributes) as $group) {
            $methodState = $this->buildMethodRouteState($group['infos']);
            foreach ($methodState['routeAttributes'] as $routeAttribute) {
                $this->connectMethodRoute(
                    $routeAttribute,
                    $group['methodName'],
                    $classMetadata,
                    $classState,
                    $methodState,
                );
            }
        }
    }

    /**
     * Returns grouped and sorted method-level attributes for route processing.
     *
     * @param string $className Controller class name.
     * @param list<string> $hierarchy Parent-to-child class hierarchy.
     * @param array<string, array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>> $classAttributes Attributes grouped by class.
     * @return array<int, array{methodName: string, infos: array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo>}>
     */
    protected function getMethodAttributeGroups(string $className, array $hierarchy, array $classAttributes): array
    {
        $methodGroups = [];
        $seenAttributes = [];
        foreach ($hierarchy as $hierarchyClass) {
            if (!isset($classAttributes[$hierarchyClass])) {
                continue;
            }
            $infos = $classAttributes[$hierarchyClass];
            foreach ($infos as $info) {
                if ($info->target->type !== AttributeTargetType::METHOD) {
                    continue;
                }
                if (
                    $info->target->methodVisibility !== null
                    && $info->target->methodVisibility !== MethodVisibility::PUBLIC
                ) {
                    continue;
                }
                $methodName = $info->target->name;
                if (str_starts_with($methodName, '__')) {
                    continue;
                }
                $declaringClass = $info->target->declaringClass ?? $hierarchyClass;
                if ($declaringClass === '') {
                    continue;
                }
                $methodKey = $declaringClass . '::' . $methodName;
                $attributeKey = $methodName . ':' . $info->attributeName . ':' . $info->lineNumber;
                if (isset($seenAttributes[$attributeKey])) {
                    continue;
                }
                $seenAttributes[$attributeKey] = true;
                if (!isset($methodGroups[$methodKey])) {
                    $methodGroups[$methodKey] = [
                        'methodName' => $methodName,
                        'infos' => [],
                    ];
                }
                $methodGroups[$methodKey]['infos'][] = $info;
            }
        }

        return array_values($methodGroups);
    }

    /**
     * Builds method-level route state from a method's attribute metadata.
     *
     * @param array<int, \Cake\AttributeResolver\ValueObject\AttributeInfo> $infos Method attribute metadata.
     * @return array{routeAttributes: array<int, \Cake\Routing\Attribute\Route>, methodMiddleware: array<int, string>, methodExtensions: array<int, string>|null}
     */
    protected function buildMethodRouteState(array $infos): array
    {
        $routeAttributes = [];
        $methodMiddleware = [];
        $methodExtensions = null;

        foreach ($infos as $info) {
            $instance = $info->getInstance();
            if ($instance instanceof RouteAttribute) {
                $routeAttributes[] = $instance;

                continue;
            }
            if ($instance instanceof Middleware) {
                $methodMiddleware = $this->mergeUniqueStrings($methodMiddleware, $instance->names);

                continue;
            }
            if ($instance instanceof Extensions) {
                $methodExtensions = $instance->extensions;
            }
        }

        return [
            'routeAttributes' => $routeAttributes,
            'methodMiddleware' => $methodMiddleware,
            'methodExtensions' => $methodExtensions,
        ];
    }

    /**
     * Connects one method route from merged class-level and method-level state.
     *
     * @param \Cake\Routing\Attribute\Route $routeAttribute Route attribute instance.
     * @param string $methodName Controller method name.
     * @param array{plugin: string|null, controller: string, prefix: string|null, prefixPath: string} $classMetadata Controller metadata.
     * @param array{
     *     scopePath: string,
     *     scopeNamePrefix: string,
     *     scopeDefaults: array<string, mixed>,
     *     scopePatterns: array<string, mixed>,
     *     scopeHost: string|null,
     *     routeClass: string,
     *     classMiddleware: array<int, string>,
     *     classExtensions: array<int, string>,
     *     resourceAttributes: array<int, \Cake\Routing\Attribute\Resource>,
     *     prefixName: string|null,
     *     prefixPath: string
     * } $classState Class route state.
     * @param array{routeAttributes: array<int, \Cake\Routing\Attribute\Route>, methodMiddleware: array<int, string>, methodExtensions: array<int, string>|null} $methodState Method route state.
     * @return void
     */
    protected function connectMethodRoute(
        RouteAttribute $routeAttribute,
        string $methodName,
        array $classMetadata,
        array $classState,
        array $methodState,
    ): void {
        $path = $this->normalizePath($classState['prefixPath'] . $classState['scopePath'] . $routeAttribute->path);
        $defaults = array_merge(
            $classState['scopeDefaults'],
            $routeAttribute->defaults,
            [
                'plugin' => $classMetadata['plugin'],
                'controller' => $classMetadata['controller'],
                'action' => $methodName,
            ],
        );
        if ($classState['prefixName'] !== null && $classState['prefixName'] !== '') {
            $defaults['prefix'] = $classState['prefixName'];
        }

        $options = array_merge($classState['scopePatterns'], $routeAttribute->patterns);
        if ($routeAttribute->name !== null) {
            $options['_name'] = $classState['scopeNamePrefix'] . $routeAttribute->name;
        }
        $argsByName = [];
        if ($routeAttribute->pass !== null) {
            $options['pass'] = array_values($routeAttribute->pass);
            $argsByName = $this->buildArgsByNameMap($routeAttribute->pass);
        } else {
            $placeholders = $this->extractPathPlaceholders($path);
            if ($placeholders !== []) {
                $options['pass'] = $placeholders;
                $argsByName = $this->buildArgsByNameMap($placeholders);
            }
        }
        if ($argsByName !== []) {
            $defaults['_argsByName'] = $argsByName;
        }
        if ($routeAttribute->persist !== []) {
            $options['persist'] = $routeAttribute->persist;
        }

        $host = $routeAttribute->host ?? $classState['scopeHost'];
        if ($host !== null) {
            $options['_host'] = $host;
        }

        $effectiveRouteClass = $routeAttribute->routeClass ?? $classState['routeClass'];
        if ($effectiveRouteClass !== '') {
            $options['routeClass'] = $effectiveRouteClass;
        }

        $effectiveExtensions = $methodState['methodExtensions'] ?? $classState['classExtensions'];
        if ($effectiveExtensions !== []) {
            $options['_ext'] = $effectiveExtensions;
        }

        $middleware = $this->mergeUniqueStrings(
            $this->routeBuilder->getMiddleware(),
            $classState['classMiddleware'],
            $methodState['methodMiddleware'],
        );
        if ($middleware !== []) {
            $options['_middleware'] = $middleware;
        }

        $defaults = $this->extractSpecialDefaults($defaults, $options);

        if ($routeAttribute->methods !== []) {
            $requestMethods = $routeAttribute->methods;
            $defaults['_method'] = count($routeAttribute->methods) === 1
                ? $requestMethods[0]
                : $requestMethods;
        }

        $this->routeBuilder->connect($path, $defaults, $options);
    }

    /**
     * Extracts placeholder names from a route path.
     *
     * @param string $path Route path.
     * @return array<int, string>
     */
    protected function extractPathPlaceholders(string $path): array
    {
        preg_match_all('#\\{([a-z][a-z0-9-_]*)\\}#i', $path, $namedElements, PREG_SET_ORDER);

        return array_values(array_map(static fn(array $match): string => $match[1], $namedElements));
    }

    /**
     * Builds a parameter-name to positional-index map from route pass definitions.
     *
     * @param array<int|string, string> $pass Route pass definitions.
     * @return array<string, int>
     */
    protected function buildArgsByNameMap(array $pass): array
    {
        $argsByName = [];
        $position = 0;
        foreach ($pass as $key => $value) {
            if (is_string($key) && $key !== '') {
                $argsByName[$key] = $position;
            }
            if (is_string($value) && $value !== '') {
                $argsByName[$value] = $position;
            }
            $position++;
        }

        return $argsByName;
    }

    /**
     * Extracts plugin, controller, and prefix metadata from a controller class name.
     *
     * @param string $className Controller class name.
     * @param string|null $pluginName Plugin name from resolver metadata.
     * @return array{plugin: string|null, controller: string, prefix: string|null, prefixPath: string}|null
     */
    protected function extractControllerMetadata(string $className, ?string $pluginName): ?array
    {
        if (!str_contains($className, '\\Controller\\')) {
            return null;
        }

        [, $controllerPath] = explode('\\Controller\\', $className, 2);
        $parts = explode('\\', $controllerPath);
        $controllerClass = (string)array_pop($parts);

        if (!str_ends_with($controllerClass, 'Controller')) {
            return null;
        }

        $prefix = null;
        $prefixPath = '';
        if ($parts !== []) {
            $prefixSegments = array_map(static fn(string $segment): string => Inflector::camelize($segment), $parts);
            $prefix = implode('/', $prefixSegments);
            $prefixPath = '/' . implode('/', array_map(Inflector::dasherize(...), $parts));
        }

        return [
            'plugin' => $pluginName,
            'controller' => substr($controllerClass, 0, -10),
            'prefix' => $prefix,
            'prefixPath' => $prefixPath,
        ];
    }

    /**
     * Normalizes an attribute route path to start with a slash and remove duplicates.
     *
     * @param string $path Raw route path.
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        if ($path === '') {
            return '/';
        }
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return (string)preg_replace('#/+#', '/', $path);
    }

    /**
     * Moves special route defaults into the route options array.
     *
     * @param array<string, mixed> $defaults Route defaults.
     * @param array<string, mixed> $options Route connect options.
     * @return array<string, mixed>
     */
    protected function extractSpecialDefaults(array $defaults, array &$options): array
    {
        foreach (['_host', '_port', '_https', '_scheme', '_ext'] as $key) {
            if (!array_key_exists($key, $defaults)) {
                continue;
            }
            $options[$key] = $defaults[$key];
            unset($defaults[$key]);
        }

        return $defaults;
    }

    /**
     * Merge multiple string lists while preserving order and uniqueness.
     *
     * @param array<int, string> ...$lists Input lists.
     * @return array<int, string>
     */
    protected function mergeUniqueStrings(array ...$lists): array
    {
        $seen = [];
        $result = [];

        foreach ($lists as $list) {
            foreach ($list as $value) {
                if (isset($seen[$value])) {
                    continue;
                }
                $seen[$value] = true;
                $result[] = $value;
            }
        }

        return $result;
    }
}
