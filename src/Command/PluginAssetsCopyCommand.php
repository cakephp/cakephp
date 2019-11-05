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
 * Command for copying plugin assets to app's webroot.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PluginAssetsCopyCommand extends Command
{
    use PluginAssetsTrait;

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'plugin assets copy';
    }

    /**
     * Execute the command
     *
     * Copying plugin assets to app's webroot. For vendor namespaced plugin,
     * parent folder for vendor name are created if required.
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
        $overwrite = (bool)$args->getOption('overwrite');
        $this->_process($this->_list($name), true, $overwrite);

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
            'Copy plugin assets to app\'s webroot.',
        ])->addArgument('name', [
            'help' => 'A specific plugin you want to copy assets for.',
            'optional' => true,
        ])->addOption('overwrite', [
            'help' => 'Overwrite existing symlink / folder / files.',
            'default' => false,
            'boolean' => true,
        ]);

        return $parser;
    }
}
