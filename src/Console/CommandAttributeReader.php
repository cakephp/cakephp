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
namespace Cake\Console;

use Cake\AttributeResolver\AttributeCollection;
use Cake\AttributeResolver\AttributeResolver;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\AttributeResolver\ValueObject\AttributeInfo;
use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;
use Cake\Console\Exception\CommandAttributeException;
use Throwable;

/**
 * Applies command metadata and inherited parser declarations.
 *
 * @internal
 */
class CommandAttributeReader
{
    /**
     * Read the command identity declared directly on a class.
     *
     * @param class-string<\Cake\Console\BaseCommand> $className Command class.
     * @return \Cake\Console\Attribute\AsCommand|null
     */
    public static function command(string $className): ?AsCommand
    {
        $attributes = static::attributes($className)->withAttribute(AsCommand::class);
        if (count($attributes) > 1) {
            throw new CommandAttributeException(sprintf('Command `%s` declares AsCommand more than once.', $className));
        }
        $info = $attributes->first();
        if ($info === null) {
            return null;
        }

        /** @var \Cake\Console\Attribute\AsCommand */
        return static::instantiate($info);
    }

    /**
     * Apply static definitions before the command's runtime parser hook.
     *
     * @param class-string<\Cake\Console\BaseCommand> $className Command class.
     * @param \Cake\Console\ConsoleOptionParser $parser Parser to configure.
     * @return void
     */
    public static function configure(string $className, ConsoleOptionParser $parser): void
    {
        try {
            $metadata = static::command($className);
            if ($metadata?->help !== null) {
                $parser->setDescription([$parser->getDescription(), ...(array)$metadata->help]);
            }
            if ($metadata?->epilog !== null) {
                $parser->setEpilog($metadata->epilog);
            }

            $arguments = [];
            $argumentIndexes = [];
            $options = [];
            $hierarchy = array_reverse(array_values(class_parents($className)));
            $hierarchy[] = $className;
            foreach ($hierarchy as $parent) {
                $seen = [];
                foreach (static::attributes($parent)->withAttribute([Argument::class, Option::class]) as $info) {
                    $attribute = static::instantiate($info);
                    assert($attribute instanceof Argument || $attribute instanceof Option);
                    if (isset($seen[$info->attributeName][$attribute->name])) {
                        throw new CommandAttributeException(sprintf(
                            'Command `%s` declares %s `%s` more than once.',
                            $parent,
                            $info->attributeName,
                            $attribute->name,
                        ));
                    }
                    $seen[$info->attributeName][$attribute->name] = true;
                    if ($attribute instanceof Argument) {
                        $arguments[$attribute->name] = $attribute;
                        if ($attribute->index !== null) {
                            $argumentIndexes[$attribute->name] = $attribute->index;
                        }
                    } else {
                        $options[$attribute->name] = $attribute;
                    }
                }
            }

            $positions = [];
            foreach ($arguments as $name => $argument) {
                $index = $argumentIndexes[$name] ?? count($positions);
                if ($index < 0 || isset($positions[$index])) {
                    throw new CommandAttributeException(sprintf('Invalid or duplicate argument index `%s`.', $index));
                }
                $positions[$index] = $argument;
            }
            ksort($positions);
            foreach ($positions as $index => $argument) {
                $settings = get_object_vars($argument);
                unset($settings['name']);
                $settings['index'] = $index;
                $parser->addArgument($argument->name, $settings);
            }
            foreach ($options as $option) {
                $settings = get_object_vars($option);
                unset($settings['name']);
                if ($option->short !== '' && isset($parser->options()[$option->name])) {
                    $parser->removeOption($option->name);
                }
                $parser->addOption($option->name, $settings);
            }
        } catch (Throwable $exception) {
            throw new CommandAttributeException(
                sprintf('Invalid attributes on command `%s`: %s', $className, $exception->getMessage()),
                previous: $exception,
            );
        }
    }

    /**
     * Read supported attributes and reject declarations on unsupported targets.
     *
     * @param class-string $className Class to inspect.
     * @return \Cake\AttributeResolver\AttributeCollection
     */
    protected static function attributes(string $className): AttributeCollection
    {
        $attributes = AttributeResolver::forClass($className)
            ->withAttribute([AsCommand::class, Argument::class, Option::class]);
        foreach ($attributes as $info) {
            if ($info->target->type !== AttributeTargetType::CLASS_) {
                throw new CommandAttributeException(sprintf(
                    'Command attribute `%s` on `%s` must target a class.',
                    $info->attributeName,
                    $className,
                ));
            }
        }

        return $attributes;
    }

    /**
     * Instantiate a declaration with its command class included in errors.
     *
     * @param \Cake\AttributeResolver\ValueObject\AttributeInfo $info Attribute metadata.
     * @return object
     */
    protected static function instantiate(AttributeInfo $info): object
    {
        try {
            return $info->getInstance();
        } catch (Throwable $exception) {
            throw new CommandAttributeException(
                sprintf('Invalid command attribute on `%s`: %s', $info->className, $exception->getMessage()),
                previous: $exception,
            );
        }
    }
}
