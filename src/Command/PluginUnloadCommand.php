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
use Cake\Utility\Hash;

/**
 * Command for unloading plugins.
 */
class PluginUnloadCommand extends Command
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
        return 'plugin unload';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Command for unloading plugins.';
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

        $result = $this->modifyConfigFile($plugin);
        if ($result === null) {
            $io->success('Plugin removed from `CONFIG/plugins.php`');

            return static::CODE_SUCCESS;
        }

        $io->err($result);

        return static::CODE_ERROR;
    }

    /**
     * Modify the plugins config file.
     *
     * @param string $plugin Plugin name.
     * @return string|null
     */
    protected function modifyConfigFile(string $plugin): ?string
    {
        // phpcs:ignore
        $config = @include $this->configFile;
        if (!is_array($config)) {
            return '`CONFIG/plugins.php` not found or does not return an array';
        }

        $config = Hash::normalize($config);
        if (!array_key_exists($plugin, $config)) {
            return sprintf('Plugin `%s` could not be found', $plugin);
        }

        unset($config[$plugin]);

        if (class_exists(VarExporter::class)) {
            $array = VarExporter::export($config);
        } else {
            $array = var_export($config, true);
        }
        $contents = '<?php' . "\n" . 'return ' . $array . ';';

        if (file_put_contents($this->configFile, $contents)) {
            return null;
        }

        return 'Failed to update `CONFIG/plugins.php`';
    }

    /**
     * Get the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            static::getDescription(),
        )
        ->addArgument('plugin', [
            'help' => 'Name of the plugin to unload.',
            'required' => true,
        ]);

        return $parser;
    }
}
