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
 * Command for symlinking / copying plugin assets to app's webroot.
 */
class PluginAssetsSymlinkCommand extends Command
{
    use PluginAssetsTrait;

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'plugin assets symlink';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return "Symlink (copy as fallback) plugin assets to app's webroot.";
    }

    /**
     * Execute the command
     *
     * Attempt to symlink plugin assets to app's webroot. If symlinking fails it
     * fallbacks to copying the assets. For vendor namespaced plugin, parent folder
     * for vendor name are created if required.
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
        $relative = (bool)$args->getOption('relative');
        $this->_process($this->_list($name), false, $overwrite, $relative);

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
        $parser->setDescription(
            static::getDescription(),
        )->addArgument('name', [
            'help' => 'A specific plugin you want to symlink assets for.',
            'required' => false,
        ])->addOption('overwrite', [
            'help' => 'Overwrite existing symlink / folder / files.',
            'default' => false,
            'boolean' => true,
        ])->addOption('relative', [
            'help' => 'If symlink should be relative.',
            'default' => false,
            'boolean' => true,
        ]);

        return $parser;
    }
}
