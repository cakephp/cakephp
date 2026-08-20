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
namespace Cake\Event;

use Cake\AttributeResolver\AttributeResolver;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\Event\Attribute\EventListener;
use Cake\Event\Exception\EventAttributeException;
use Closure;
use ReflectionMethod;

/**
 * Connects event listeners declared via PHP attributes to an event manager.
 *
 * Reads event listener attribute metadata from the configured AttributeResolver
 * collection and registers the resolved callables on the provided EventManagerInterface.
 *
 * Usage within `Application::events()`:
 *
 * ```php
 * public function events(EventManagerInterface $eventManager): EventManagerInterface
 * {
 *     new AttributeEventListenerConnector(
 *         $eventManager,
 *         $this->getContainer()->get(...),
 *     )->connect();
 *
 *     return $eventManager;
 * }
 * ```
 *
 * Or using the connector directly with a custom config:
 *
 * ```php
 * (new AttributeEventListenerConnector($eventManager))->connect('myConfig');
 * ```
 */
class AttributeEventListenerConnector
{
    /**
     * Resolves listener class names to instances.
     *
     * @var \Closure(string): object
     */
    private readonly Closure $listenerResolver;

    /**
     * Constructs an AttributeEventListenerConnector.
     *
     * @param \Cake\Event\EventManagerInterface $eventManager Event manager to attach listeners to.
     * @param callable|null $listenerResolver Resolver used to create listener instances.
     */
    public function __construct(
        protected readonly EventManagerInterface $eventManager,
        ?callable $listenerResolver = null,
    ) {
        $this->listenerResolver = $listenerResolver === null
            ? static fn(string $className): object => new $className()
            : Closure::fromCallable($listenerResolver);
    }

    /**
     * Resolves all EventListener attributes from the configured resolver collection
     * and registers the discovered listeners on the event manager.
     *
     * @param string $config Attribute resolver config name.
     * @return void
     */
    public function connect(string $config = 'default'): void
    {
        $collection = AttributeResolver::collection($config)->withAttribute(EventListener::class);

        /** @var array<string, list<\Cake\AttributeResolver\ValueObject\AttributeInfo>> $classAttributes */
        $classAttributes = [];
        foreach ($collection as $attributeInfo) {
            $classAttributes[$attributeInfo->className][] = $attributeInfo;
        }

        ksort($classAttributes);

        foreach ($classAttributes as $className => $infos) {
            usort($infos, fn(AttributeInfo $a, AttributeInfo $b): int => $a->lineNumber <=> $b->lineNumber);
            $this->connectListenerClass($className, $infos);
        }
    }

    /**
     * Connects all EventListener attributes declared on a single class.
     *
     * Skips the class silently when it cannot be instantiated (e.g. abstract classes,
     * interfaces, or traits). Throws EventAttributeException for invalid declarations
     * on concrete classes (missing methods, non-public methods).
     *
     * @param string $className Fully qualified class name to process.
     * @param list<\Cake\AttributeResolver\ValueObject\AttributeInfo> $infos Attribute metadata sorted by line number.
     * @return void
     * @throws \Cake\Event\Exception\EventAttributeException When a listener method cannot be resolved.
     */
    protected function connectListenerClass(string $className, array $infos): void
    {
        if (!class_exists($className)) {
            return;
        }

        if (!$infos[0]->target->isInstantiableDeclaringType()) {
            return;
        }

        $registrations = $this->resolveRegistrations($className, $infos);
        if ($registrations === []) {
            return;
        }

        $listener = $this->createListener($className);
        foreach ($registrations as $registration) {
            $options = $registration['priority'] === null
                ? []
                : ['priority' => $registration['priority']];
            $this->eventManager->on(
                $registration['event'],
                $listener->{$registration['method']}(...),
                $options,
            );
        }
    }

    /**
     * Resolves the listener registrations for a class, validating each declaration.
     *
     * Returns a deduplicated list of registrations ordered by line number.
     *
     * @param string $className Fully qualified class name.
     * @param list<\Cake\AttributeResolver\ValueObject\AttributeInfo> $infos Attribute metadata sorted by line number.
     * @return list<array{event: string, method: string, priority: int|null}>
     * @throws \Cake\Event\Exception\EventAttributeException When a listener method cannot be resolved.
     */
    protected function resolveRegistrations(string $className, array $infos): array
    {
        $seen = [];
        $registrations = [];

        foreach ($infos as $info) {
            /** @var \Cake\Event\Attribute\EventListener $attribute */
            $attribute = $info->getInstance(EventListener::class);

            $methodName = $this->resolveMethodName($info, $attribute, $className);

            if (!method_exists($className, $methodName)) {
                throw new EventAttributeException(sprintf(
                    'Method "%s::%s()" does not exist. '
                    . "Declare it or update the `method` argument of `#[EventListener('%s')]` "
                    . 'in %s at line %d.',
                    $className,
                    $methodName,
                    $attribute->event,
                    $info->filePath,
                    $info->lineNumber,
                ));
            }

            $reflection = new ReflectionMethod($className, $methodName);
            if (!$reflection->isPublic()) {
                throw new EventAttributeException(sprintf(
                    'Method "%s::%s()" must be public to be used as an event listener. '
                    . 'Declared on event "%s" in %s at line %d.',
                    $className,
                    $methodName,
                    $attribute->event,
                    $info->filePath,
                    $info->lineNumber,
                ));
            }

            $priority = $attribute->priority;
            $priorityKey = $priority === null ? 'default' : 'priority:' . $priority;
            $key = $attribute->event . "\0" . $priorityKey . "\0" . $methodName;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $registrations[] = [
                'event' => $attribute->event,
                'method' => $methodName,
                'priority' => $priority,
            ];
        }

        return $registrations;
    }

    /**
     * Resolves the listener method name for the given attribute and target.
     *
     * Resolution order:
     *  1. For method-level attributes: the name of the method the attribute is placed on.
     *  2. For class-level attributes: the explicit `method` argument when provided.
     *  3. For class-level attributes: `__invoke` when present on the class.
     *  4. For class-level attributes: a name inferred from the event name.
     *
     * @param \Cake\AttributeResolver\ValueObject\AttributeInfo $info Attribute metadata.
     * @param \Cake\Event\Attribute\EventListener $attribute Instantiated attribute.
     * @param string $className Fully qualified class name.
     * @return string Resolved method name.
     */
    protected function resolveMethodName(AttributeInfo $info, EventListener $attribute, string $className): string
    {
        if ($info->target->type === AttributeTargetType::METHOD) {
            return $info->target->name;
        }

        if ($attribute->method !== null) {
            return $attribute->method;
        }

        if (method_exists($className, '__invoke')) {
            return '__invoke';
        }

        return $this->inferMethodName($attribute->event);
    }

    /**
     * Infers a method name from an event name.
     *
     * Segments split on `.` are each uppercased and prefixed with `on`.
     * For example: `Order.afterPlace` → `onOrderAfterPlace`.
     *
     * @param string $eventName Event name.
     * @return string Inferred method name.
     */
    protected function inferMethodName(string $eventName): string
    {
        $parts = explode('.', $eventName);
        $parts = array_map(ucfirst(...), $parts);

        return 'on' . implode('', $parts);
    }

    /**
     * Resolves a listener class to an instance.
     *
     * Uses the configured listener resolver. By default, listener classes are
     * instantiated directly.
     *
     * @param string $className Fully qualified class name to resolve.
     * @return object Listener instance.
     */
    protected function createListener(string $className): object
    {
        $listener = ($this->listenerResolver)($className);
        if (!is_object($listener)) {
            throw new EventAttributeException(sprintf(
                'Listener resolver must return an object for "%s".',
                $className,
            ));
        }

        return $listener;
    }
}
