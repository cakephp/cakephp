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

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Command for unloading plugins.
 */
class PluginUnloadCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'plugin unload';
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
        $plugin = $args->getArgument('plugin');
        if (!$plugin) {
            $io->err('You must provide a plugin name in CamelCase format.');
            $io->err('To unload an "Example" plugin, run `cake plugin unload Example`.');

            return static::CODE_ERROR;
        }

        $app = APP . 'Application.php';
        if (file_exists($app) && $this->modifyApplication($app, $plugin)) {
            $io->out('');
            $io->out(sprintf('%s modified', $app));

            return static::CODE_SUCCESS;
        }

        return static::CODE_ERROR;
    }

    /**
     * Modify the application class.
     *
     * @param string $app Path to the application to update.
     * @param string $plugin Name of plugin.
     * @return bool If modify passed.
     */
    protected function modifyApplication(string $app, string $plugin): bool
    {
        $plugin = preg_quote($plugin, '/');
        $finder = "/
            # whitespace and addPlugin call
            \s*\\\$this\-\>addPlugin\(
            # plugin name in quotes of any kind
            \s*['\"]{$plugin}['\"]
            # method arguments assuming a literal array with multiline args
            (\s*,[\s\\n]*\[(\\n.*|.*){0,5}\][\\n\s]*)?
            # closing paren of method
            \);/mx";

        $content = file_get_contents($app);
        $newContent = preg_replace($finder, '', $content);

        if ($newContent === $content) {
            return false;
        }

        file_put_contents($app, $newContent);

        return true;
    }

    /**
     * Get the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription([
            'Command for unloading plugins.',
        ])
        ->addArgument('plugin', [
            'help' => 'Name of the plugin to unload.',
        ]);

        return $parser;
    }
}
