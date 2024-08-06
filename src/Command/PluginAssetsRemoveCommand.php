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
 * Command for removing plugin assets from app's webroot.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PluginAssetsRemoveCommand extends Command
{
    use PluginAssetsTrait;

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'plugin assets remove';
    }

    /**
     * Execute the command
     *
     * Remove plugin assets from app's webroot.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->io = $io;
        $this->args = $args;

        $name = $args->getArgument('name');
        $plugins = $this->_list($name);

        foreach ($plugins as $plugin => $config) {
            $this->io->out();
            $this->io->out('For plugin: ' . $plugin);
            $this->io->hr();

            $this->_remove($config);
        }

        $this->io->out();
        $this->io->out('Done');

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
        $parser->setDescription([
            "Remove plugin assets from app's webroot.",
        ])->addArgument('name', [
            'help' => 'A specific plugin you want to remove.',
            'required' => false,
        ]);

        return $parser;
    }
}
