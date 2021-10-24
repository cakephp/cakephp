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
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;

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
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->io = $io;
        $this->args = $args;

        $plugin = $args->getArgument('plugin') ?? '';
        try {
            Plugin::getCollection()->findPath($plugin);
        } catch (MissingPluginException $e) {
            $this->io->err($e->getMessage());
            $this->io->err('Ensure you have the correct spelling and casing.');

            return static::CODE_ERROR;
        }

        $app = APP . 'Application.php';
        if (file_exists($app)) {
            $this->modifyApplication($app, $plugin);

            return static::CODE_SUCCESS;
        }

        return static::CODE_ERROR;
    }

    /**
     * Modify the application class
     *
     * @param string $app The Application file to modify.
     * @param string $plugin The plugin name to add.
     * @return void
     */
    protected function modifyApplication(string $app, string $plugin): void
    {
        $contents = file_get_contents($app);

        // Find start of bootstrap
        if (!preg_match('/^(\s+)public function bootstrap(?:\s*)\(\)/mu', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $this->io->err('Your Application class does not have a bootstrap() method. Please add one.');
            $this->abort();
        }

        $offset = $matches[0][1];
        $indent = $matches[1][0];

        // Find closing function bracket
        if (!preg_match("/^$indent\}\n$/mu", $contents, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $this->io->err('Your Application class does not have a bootstrap() method. Please add one.');
            $this->abort();
        }

        $append = "$indent    \$this->addPlugin('%s');\n";
        $insert = str_replace(', []', '', sprintf($append, $plugin));

        $offset = $matches[0][1];
        $contents = substr_replace($contents, $insert, $offset, 0);

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
        ->addArgument('plugin', [
            'help' => 'Name of the plugin to load. Must be in CamelCase format. Example: cake plugin load Example',
            'required' => true,
        ]);

        return $parser;
    }
}
