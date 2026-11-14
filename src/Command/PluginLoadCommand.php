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
use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\Core\PluginInterface;
use Cake\Utility\Hash;

/**
 * Command for loading plugins.
 */
#[AsCommand('plugin load', description: 'Command for loading plugins.')]
#[Argument(
    'plugin',
    help: 'Name of the plugin to load. Must be in CamelCase format. Example: cake plugin load Example',
    required: true,
)]
#[Option('only-debug', help: 'Load the plugin only when `debug` is enabled.', boolean: true)]
#[Option('only-cli', help: 'Load the plugin only for CLI.', boolean: true)]
#[Option('optional', help: 'Do not throw an error if the plugin is not available.', boolean: true)]
#[Option('no-bootstrap', help: 'Do not run the `bootstrap()` hook.', boolean: true)]
#[Option('no-console', help: 'Do not run the `console()` hook.', boolean: true)]
#[Option('no-middleware', help: 'Do not run the `middleware()` hook.', boolean: true)]
#[Option('no-routes', help: 'Do not run the `routes()` hook.', boolean: true)]
#[Option('no-services', help: 'Do not run the `services()` hook.', boolean: true)]
class PluginLoadCommand extends Command
{
    /**
     * @var array<string>
     */
    protected static array $devTags = ['dev', 'testing', 'static analysis'];

    /**
     * @var array<string>
     */
    protected static array $cliTags = ['cli', 'command line', 'shell'];

    /**
     * Config file
     *
     * @var string
     */
    protected string $configFile = CONFIG . 'plugins.php';

    /**
     * Execute the command
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $plugin = (string)$this->args->getArgument('plugin');
        $options = [];
        if ($this->args->getOption('only-debug')) {
            $options['onlyDebug'] = true;
        }
        if ($this->args->getOption('only-cli')) {
            $options['onlyCli'] = true;
        }
        if ($this->args->getOption('optional')) {
            $options['optional'] = true;
        }

        foreach (PluginInterface::VALID_HOOKS as $hook) {
            if ($this->args->getOption('no-' . $hook)) {
                $options[$hook] = false;
            }
        }

        $path = null;
        try {
            $path = Plugin::getCollection()->findPath($plugin);
        } catch (MissingPluginException $e) {
            if (empty($options['optional'])) {
                $this->io->error($e->getMessage());
                $this->io->error('Ensure you have the correct spelling and casing.');

                return static::CODE_ERROR;
            }
        }

        $recommendations = $path ? $this->recommendations($path) : [];
        foreach ($recommendations as $name => $v) {
            if (isset($options[$name]) && $options[$name] === $v) {
                continue;
            }

            $option = $name . ': ' . ($v ? 'true' : 'false');
            $question = 'Based on the plugin composer keywords, this seems to be `' . $option . '`. ';
            $question .= 'Do you want to change this?';
            $in = $this->io->askChoice($question, ['y', 'n'], 'y');
            if ($in !== 'y') {
                continue;
            }

            $options[$name] = $v;
        }

        $result = $this->modifyConfigFile($plugin, $options);
        if ($result === static::CODE_ERROR) {
            $this->io->error('Failed to update `CONFIG/plugins.php`');
        }

        $this->io->success('Plugin added successfully to `CONFIG/plugins.php`');

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
     * @param string $path
     * @return array<string, bool>
     */
    protected function recommendations(string $path): array
    {
        $file = $path . 'composer.json';
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        $array = $content ? json_decode($content, true) : [];
        $keywords = $array['keywords'] ?? [];
        if (!$keywords) {
            return [];
        }

        $recommendations = [];
        foreach (static::$devTags as $tag) {
            if (in_array($tag, $keywords, true)) {
                $recommendations['onlyDebug'] = true;
            }
        }
        foreach (static::$cliTags as $tag) {
            if (in_array($tag, $keywords, true)) {
                $recommendations['onlyCli'] = true;
            }
        }

        if (!empty($recommendations['onlyDebug'])) {
            $recommendations['optional'] = true;
        }

        return $recommendations;
    }
}
