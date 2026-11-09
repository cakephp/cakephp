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
namespace Cake\Command;

use Cake\Console\CommandCollection;

/**
 * Registers built-in framework commands without filesystem discovery.
 *
 * @internal
 */
class CoreCommands
{
    /**
     * Built-in commands available independently of application attribute configuration.
     *
     * @var list<class-string<\Cake\Console\BaseCommand>>
     */
    protected const array CLASSES = [
        AttributesInspectCommand::class,
        AttributesListCommand::class,
        AttributesWarmCommand::class,
        CacheClearCommand::class,
        CacheClearGroupCommand::class,
        CacheClearallCommand::class,
        CacheListCommand::class,
        CompletionCommand::class,
        CounterCacheCommand::class,
        I18nCommand::class,
        I18nExtractCommand::class,
        I18nInitCommand::class,
        PluginAssetsCopyCommand::class,
        PluginAssetsRemoveCommand::class,
        PluginAssetsSymlinkCommand::class,
        PluginListCommand::class,
        PluginLoadCommand::class,
        PluginLoadedCommand::class,
        PluginUnloadCommand::class,
        RoutesCheckCommand::class,
        RoutesCommand::class,
        RoutesGenerateCommand::class,
        SchemacacheBuildCommand::class,
        SchemacacheClearCommand::class,
        ServerCommand::class,
        VersionCommand::class,
    ];

    /**
     * Add core commands while preserving explicitly registered names.
     *
     * @param \Cake\Console\CommandCollection $commands Command registry.
     * @return \Cake\Console\CommandCollection
     */
    public static function addTo(CommandCollection $commands): CommandCollection
    {
        foreach (static::CLASSES as $class) {
            $name = $class::defaultName();
            if (!$commands->has($name)) {
                $commands->add($name, $class);
            }
        }

        return $commands;
    }
}
