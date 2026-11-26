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
namespace Cake\Core;

use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use Throwable;

/**
 * Reads native dependency declarations when explicitly enabled.
 *
 * @internal
 */
trait AttributeDeclarationsTrait
{
    /**
     * Overrides App.attributeDeclarations for this owner, or null to use the application setting.
     *
     * @var bool|null
     */
    protected ?bool $attributeDeclarationsEnabled = null;

    /**
     * Caches inherited declaration bindings and creates fresh instances for each owner.
     *
     * @return array<string, array<string, \Cake\Core\ClassDeclaration>>
     */
    protected function attributeDeclarations(): array
    {
        if (!($this->attributeDeclarationsEnabled ?? Configure::read('App.attributeDeclarations', false))) {
            return [];
        }

        /** @var array<class-string, list<array{class-string, \ReflectionAttribute<\Cake\Core\ClassDeclaration>, string, string}>> $cache */
        static $cache = [];
        $attributes = $cache[static::class] ?? null;
        $resolve = $attributes === null;
        if ($resolve) {
            $hierarchy = array_reverse(array_values(class_parents($this)));
            $hierarchy[] = static::class;
            $attributes = [];
            foreach ($hierarchy as $className) {
                foreach (
                    new ReflectionClass($className)->getAttributes(
                        ClassDeclaration::class,
                        ReflectionAttribute::IS_INSTANCEOF,
                    ) as $attribute
                ) {
                    $attributes[] = [$className, $attribute, null, null];
                }
            }
        }

        if ($attributes === []) {
            return $cache[static::class] = [];
        }

        $declarations = [];
        $bindings = [];
        $seen = [];
        foreach ($attributes as [$className, $attribute, $domain, $alias]) {
            try {
                $declaration = $attribute->newInstance();
                if ($resolve) {
                    $ownerClasses = $declaration->getOwnerClasses();
                    if (!array_any($ownerClasses, fn(string $ownerClass): bool => $this instanceof $ownerClass)) {
                        throw new InvalidArgumentException(sprintf(
                            'This declaration requires an owner of type `%s`.',
                            implode('` or `', $ownerClasses),
                        ));
                    }
                    $declaration->validate();
                }

                $domain ??= $declaration->getDomain();
                $alias ??= $declaration->getAlias();
                if ($resolve) {
                    if (isset($seen[$className][$domain][$alias])) {
                        throw new InvalidArgumentException(sprintf(
                            'Duplicate %s declaration for alias `%s`.',
                            $domain,
                            $alias,
                        ));
                    }
                    $seen[$className][$domain][$alias] = true;
                    $bindings[$domain][$alias] = [$className, $attribute, $domain, $alias];
                }
                $declarations[$domain][$alias] = $declaration;
            } catch (Throwable $exception) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid attribute declaration `%s` on `%s` while constructing `%s`: %s',
                    $attribute->getName(),
                    $className,
                    static::class,
                    $exception->getMessage(),
                ), previous: $exception);
            }
        }

        if ($resolve) {
            $cache[static::class] = [];
            foreach ($bindings as $domainBindings) {
                foreach ($domainBindings as $binding) {
                    $cache[static::class][] = $binding;
                }
            }
        }

        return $declarations;
    }
}
