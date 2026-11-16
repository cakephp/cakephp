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

use Cake\AttributeResolver\AttributeResolver;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Exception\CommandAttributeException;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use Throwable;

/**
 * Registers attributed commands from a configured resolver collection.
 *
 * Resolves application commands first, followed by console-enabled plugins.
 * Command classes remain lazy and are instantiated by the existing factory.
 */
class AttributeCommandConnector
{
    /**
     * Set the registry receiving discovered commands.
     *
     * @param \Cake\Console\CommandCollection $commands Command registry.
     */
    public function __construct(protected readonly CommandCollection $commands)
    {
    }

    /**
     * Register commands using the same configured discovery as routes and events.
     *
     * @param string $config Attribute resolver configuration name.
     * @return void
     */
    public function connect(string $config = 'default'): void
    {
        $plugins = [];
        foreach (Plugin::getCollection()->with('console') as $plugin) {
            $plugins[$plugin->getName()] = true;
        }

        $classes = [];
        foreach (AttributeResolver::collection($config)->withAttribute(AsCommand::class) as $info) {
            if ($info->pluginName !== null && !isset($plugins[$info->pluginName])) {
                continue;
            }
            if (!$info->target->isInstantiableDeclaringType()) {
                continue;
            }
            if ($info->target->type !== AttributeTargetType::CLASS_) {
                throw new CommandAttributeException(sprintf(
                    'AsCommand on `%s` must target a class.',
                    $info->className,
                ));
            }
            if (!is_subclass_of($info->className, BaseCommand::class)) {
                throw new CommandAttributeException(sprintf(
                    'Attributed command `%s` must extend BaseCommand.',
                    $info->className,
                ));
            }
            $plugin = $info->pluginName ?? '';
            if (isset($classes[$plugin][$info->className])) {
                throw new CommandAttributeException(sprintf(
                    'Command `%s` declares AsCommand more than once.',
                    $info->className,
                ));
            }
            $classes[$plugin][$info->className] = $info;
        }
        ksort($classes);
        foreach ($classes as $plugin => $commands) {
            ksort($commands);
            foreach ($commands as $className => $info) {
                try {
                    $metadata = $info->getInstance(AsCommand::class);
                } catch (Throwable $exception) {
                    throw new CommandAttributeException(
                        sprintf('Invalid command attribute on `%s`: %s', $className, $exception->getMessage()),
                        previous: $exception,
                    );
                }
                $name = $metadata->name;
                if ($plugin === '') {
                    $this->commands->add($name, $className);
                } else {
                    $qualified = Inflector::underscore($plugin) . '.' . $name;
                    if (!$this->commands->has($name)) {
                        $this->commands->add($name, $className);
                    }
                    $this->commands->add($qualified, $className);
                }
            }
        }
    }
}
