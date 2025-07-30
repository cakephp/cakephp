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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Brick\VarExporter\VarExporter;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\Core\PluginInterface;
use Cake\Utility\Hash;

/**
 * Command for loading plugins.
 */
class PluginLoadCommand extends Command
{
    /**
     * Config file
     *
     * @var string
     */
    protected string $configFile = CONFIG . 'plugins.php';

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'plugin load';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Command for loading plugins.';
    }

    /**
     * Execute the command
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $plugin = (string)$args->getArgument('plugin');
        $options = [];
        if ($args->getOption('only-debug')) {
            $options['onlyDebug'] = true;
        }
        if ($args->getOption('only-cli')) {
            $options['onlyCli'] = true;
        }
        if ($args->getOption('optional')) {
            $options['optional'] = true;
        }

        foreach (PluginInterface::VALID_HOOKS as $hook) {
            if ($args->getOption('no-' . $hook)) {
                $options[$hook] = false;
            }
        }

        try {
            Plugin::getCollection()->findPath($plugin);
        } catch (MissingPluginException $e) {
            if (empty($options['optional'])) {
                $io->err($e->getMessage());
                $io->err('Ensure you have the correct spelling and casing.');

                return static::CODE_ERROR;
            }
        }

        $result = $this->modifyConfigFile($plugin, $options);
        if ($result === static::CODE_ERROR) {
            $io->err('Failed to update `CONFIG/plugins.php`');
        }

        $io->success('Plugin added successfully to `CONFIG/plugins.php`');

        return $result;
    }

    /**
     * Modify the plugins config file.
     *
     * @param string $plugin Plugin name.
     * @param array<string, mixed> $options Plugin options.
     * @return int
     */
    protected function modifyConfigFile(string $plugin, array $options): int
    {
        // phpcs:ignore
        $config = @include $this->configFile;
        if (!is_array($config)) {
            $config = [];
        } else {
            $config = Hash::normalize($config);
        }

        $config[$plugin] = $options;

        if (class_exists(VarExporter::class)) {
            $array = VarExporter::export($config, VarExporter::TRAILING_COMMA_IN_ARRAY);
        } else {
            $array = var_export($config, true);
        }
        $contents = '<?php' . "\n\n" . 'return ' . $array . ';' . "\n";

        if (file_put_contents($this->configFile, $contents)) {
            return static::CODE_SUCCESS;
        }

        return static::CODE_ERROR;
    }

    /**
     * Get the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription(static::getDescription())
            ->addArgument('plugin', [
                'help' => 'Name of the plugin to load. Must be in CamelCase format. Example: cake plugin load Example',
                'required' => true,
            ])
            ->addOption('only-debug', [
                'boolean' => true,
                'help' => 'Load the plugin only when `debug` is enabled.',
            ])
            ->addOption('only-cli', [
                'boolean' => true,
                'help' => 'Load the plugin only for CLI.',
            ])
            ->addOption('optional', [
                'boolean' => true,
                'help' => 'Do not throw an error if the plugin is not available.',
            ])
            ->addOption('no-bootstrap', [
                'boolean' => true,
                'help' => 'Do not run the `bootstrap()` hook.',
            ])
            ->addOption('no-console', [
                'boolean' => true,
                'help' => 'Do not run the `console()` hook.',
            ])
            ->addOption('no-middleware', [
                'boolean' => true,
                'help' => 'Do not run the `middleware()` hook..',
            ])
            ->addOption('no-routes', [
                'boolean' => true,
                'help' => 'Do not run the `routes()` hook.',
            ])
            ->addOption('no-services', [
                'boolean' => true,
                'help' => 'Do not run the `services()` hook.',
            ]);
    }
}
