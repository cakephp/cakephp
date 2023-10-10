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
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Hash;
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
     * Displays all currently available plugins.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        Plugin::getCollection();

        // phpcs:ignore
        $pluginsConfig = @include CONFIG . 'plugins.php';
        if (is_array($pluginsConfig)) {
            $pluginsConfig = Hash::normalize($pluginsConfig);
        }

        $table = [
            ['Plugin', 'onlyDebug', 'onlyCli', 'optional'],
        ];

        $plugins = Configure::read('plugins', []);
        if ($plugins && is_array($plugins)) {
            foreach ($plugins as $pluginName => $configPath) {
                if ($pluginsConfig && array_key_exists($pluginName, $pluginsConfig)) {
                    $options = $pluginsConfig[$pluginName];
                    $onlyDebug = $options['onlyDebug'] ?? false;
                    $onlyCli = $options['onlyCli'] ?? false;
                    $optional = $options['optional'] ?? false;
                    $table[] = [$pluginName, $onlyDebug ? 'X' : '', $onlyCli ? 'X' : '', $optional ? 'X' : ''];
                } else {
                    $table[] = [$pluginName, '', '', ''];
                }
            }
            $io->helper('Table')->output($table);
        } else {
            $io->warning(__d('cake', 'No plugins have been found.'));
        }

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
        $parser->setDescription('Displays all currently available plugins.');

        return $parser;
    }
}
