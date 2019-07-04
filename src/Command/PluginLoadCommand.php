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
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Command for loading plugins.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PluginLoadCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'plugin load';
    }

    /**
     * Arguments
     *
     * @var \Cake\Console\Arguments
     */
    protected $args;

    /**
     * Console IO
     *
     * @var \Cake\Console\ConsoleIo
     */
    protected $io;

    /**
     * Execute the command
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->io = $io;
        $this->args = $args;

        $plugin = $args->getArgument('plugin');
        if (!$plugin) {
            $this->io->err('You must provide a plugin name in CamelCase format.');
            $this->io->err('To load an "Example" plugin, run `cake plugin load Example`.');

            return static::CODE_ERROR;
        }

        $options = $this->makeOptions($args);

        $app = APP . 'Application.php';
        if (file_exists($app)) {
            $this->modifyApplication($app, $plugin, $options);

            return static::CODE_SUCCESS;
        }

        return static::CODE_ERROR;
    }

    /**
     * Create options string for the load call.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @return string
     */
    protected function makeOptions(Arguments $args): string
    {
        $bootstrapString = $args->getOption('bootstrap') ? "'bootstrap' => true" : '';
        $routesString = $args->getOption('routes') ? "'routes' => true" : '';

        return implode(', ', array_filter([$bootstrapString, $routesString]));
    }

    /**
     * Modify the application class
     *
     * @param string $app The Application file to modify.
     * @param string $plugin The plugin name to add.
     * @param string $options The plugin options to add
     * @return void
     */
    protected function modifyApplication(string $app, string $plugin, string $options): void
    {
        $contents = file_get_contents($app);

        $append = "\n        \$this->addPlugin('%s', [%s]);\n";
        $insert = str_replace(', []', '', sprintf($append, $plugin, $options));

        if (!preg_match('/function bootstrap\(\)(?:\s*)\:(?:\s*)void/m', $contents)) {
            $this->io->err('Your Application class does not have a bootstrap() method. Please add one.');
            $this->abort();
        } else {
            $contents = preg_replace(
                '/(function bootstrap\(\)(?:\s*)\:(?:\s*)void(?:\s+)\{)/m',
                '$1' . $insert,
                $contents
            );
        }
        file_put_contents($app, $contents);

        $this->io->out('');
        $this->io->out(sprintf('%s modified', $app));
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
            'Command for loading plugins.',
        ])
        ->addOption('bootstrap', [
            'short' => 'b',
            'help' => 'Will load bootstrap.php from plugin.',
            'boolean' => true,
            'default' => false,
        ])
        ->addOption('routes', [
            'short' => 'r',
            'help' => 'Will load routes.php from plugin.',
            'boolean' => true,
            'default' => false,
        ])
        ->addArgument('plugin', [
            'help' => 'Name of the plugin to load.',
        ]);

        return $parser;
    }
}
