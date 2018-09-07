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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * Upgrade 3.x application to 4.0.
 */
class UpgradeCommand extends Command
{
    /**
     * Is git used
     *
     * @var bool
     */
    protected $git = false;

    /**
     * App/plugin path.
     *
     * @var string
     */
    protected $path = '';

    /**
     * Execute.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->io = $io;
        $this->args = $args;

        $path = $args->getOption('path');
        if ($path) {
            $this->path = rtrim($path, '/') . DIRECTORY_SEPARATOR;
        } else {
            $this->path = APP;
        }

        $this->git = is_dir($this->path . '.git');

        if ($args->hasArgument('templates')) {
            $this->processTemplates();
        }
    }

    /**
     * Move templates to new location and rename to .php
     *
     * @return void
     */
    protected function processTemplates()
    {
        if (is_dir($this->path . 'src/Template')) {
            $this->rename($this->path . 'src/Template', $this->path . 'templates');
            $this->changeExt($this->path . 'templates');
        }

        foreach ((array)Configure::read('App.paths.plugins') as $path) {
            $this->moveDir($path);
            $this->changeExt($path);
        }
    }

    /**
     * Recursively move src/Template to Template
     *
     * @param string $path Path
     * @return void
     */
    protected function moveDir($path)
    {
        $dirIter = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::UNIX_PATHS
        );
        $iterIter = new RecursiveIteratorIterator($dirIter);
        $templateDirs = new RegexIterator(
            $iterIter,
            '/Template\/\.$/',
            RecursiveRegexIterator::REPLACE
        );

        foreach ($templateDirs as $key => $val) {
            $this->rename($val . 'Template', $val . 'Template/../../templates');
        }
    }

    /**
     * Recursively change template extension to .php
     *
     * @param string $path Path
     * @return void
     */
    protected function changeExt($path)
    {
        $dirIter = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS
        );
        $iterIter = new RecursiveIteratorIterator($dirIter);
        $templates = new RegexIterator(
            $iterIter,
            '/\.ctp$/i',
            RecursiveRegexIterator::REPLACE
        );

        foreach ($templates as $key => $val) {
            $this->rename($val . '.ctp', $val . '.php');
        }
    }

    /**
     * Rename file or directory
     *
     * @param string $source Source path.
     * @param string $dest Destination path.
     * @return void
     */
    protected function rename($source, $dest)
    {
        $this->io->out("Move $source to $dest");

        if (!$this->args->getOption('dry-run')) {
            if ($this->git) {
                exec("git mv $source $dest");
            } else {
                rename($source, $dest);
            }
        }
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to build
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->addArgument('templates', [
                'help' => 'Move templates to new location and change extension.',
            ])
            ->addOption('path', [
                'help' => 'App/plugin path',
            ])
            ->addOption('dry-run', [
                'help' => 'Dry run.',
                'boolean' => false,
            ]);

        return $parser;
    }
}
