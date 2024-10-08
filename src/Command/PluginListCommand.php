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
 * @since         5.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use Cake\Core\PluginConfig;
use function Cake\I18n\__d;

/**
 * Displays all currently available plugins.
 */
class PluginListCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'plugin list';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Displays all currently available plugins.';
    }

    /**
     * Displays all currently available plugins.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $loadedPluginsCollection = Plugin::getCollection();
        $path = (string)$args->getOption('composer-path');
        $config = PluginConfig::getAppConfig($path ?: null);

        $table = [
            ['Plugin', 'Is Loaded', 'Only Debug', 'Only CLI', 'Optional', 'Version'],
        ];

        if ($config === []) {
            $io->warning(__d('cake', 'No plugins have been found.'));

            return static::CODE_ERROR;
        }

        foreach ($config as $pluginName => $options) {
            $isLoaded = $loadedPluginsCollection->has($pluginName);
            $onlyDebug = $options['onlyDebug'] ?? false;
            $onlyCli = $options['onlyCli'] ?? false;
            $optional = $options['optional'] ?? false;
            $version = $options['version'] ?? '';
            $table[] = [
                $pluginName,
                $isLoaded ? 'X' : '',
                $onlyDebug ? 'X' : '',
                $onlyCli ? 'X' : '',
                $optional ? 'X' : '',
                $version,
            ];
        }
        $io->helper('Table')->output($table);

        return static::CODE_SUCCESS;
    }

    /**
     * Get the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(static::getDescription());
        $parser->addOption('composer-path', [
            'help' => 'The absolute path to the composer.lock file to retrieve the versions from',
        ]);

        return $parser;
    }
}
