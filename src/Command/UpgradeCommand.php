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
     * Holds info of file types to move.
     *
     * @var array
     */
    protected $types = [
        'templates' => [
            'regex' => '#/Template/\.$#',
            'from' => '/Template',
            'to' => '/../templates',
        ],
        'locales' => [
            'regex' => '#/Locale/\.$#',
            'from' => '/Locale',
            'to' => '/../resources/locales',
        ],
    ];

    /**
     * Execute.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
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

        switch ($args->getArgument('type')) {
            case 'templates':
                $this->processTemplates();
                break;
            case 'locales':
                $this->processLocales();
                break;
        }

        return null;
    }

    /**
     * Move templates to new location and rename to .php
     *
     * @return void
     */
    protected function processTemplates(): void
    {
        if (is_dir($this->path . 'src/Template')) {
            $this->rename(
                $this->path . 'src/Template',
                $this->path . 'templates'
            );
            $this->renameSubFolders($this->path . 'templates');
            $this->changeExt($this->path . 'templates');
        }

        foreach ((array)Configure::read('App.paths.plugins') as $path) {
            $this->moveDir($path, 'templates');
            $this->changeExt($path);
        }
    }

    /**
     * Move locale files to new location.
     *
     * @return void
     */
    protected function processLocales(): void
    {
        if (is_dir($this->path . 'src/Locale')) {
            $this->rename(
                $this->path . 'src/Locale',
                $this->path . 'resources/locales'
            );
        }

        foreach ((array)Configure::read('App.paths.plugins') as $path) {
            $this->moveDir($path, 'locales');
        }
    }

    /**
     * Recursively move directories.
     *
     * @param string $path Path
     * @param string $type Type
     * @return void
     */
    protected function moveDir(string $path, string $type): void
    {
        $info = $this->types[$type];

        $dirIter = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::UNIX_PATHS
        );
        $iterIter = new RecursiveIteratorIterator($dirIter);
        $templateDirs = new RegexIterator(
            $iterIter,
            $info['regex'],
            RecursiveRegexIterator::REPLACE
        );

        foreach ($templateDirs as $key => $val) {
            $this->rename(
                $val . $info['from'],
                $val . $info['to']
            );

            if ($type === 'templates') {
                $this->renameSubFolders($val . '/../templates');
            }
        }
    }

    /**
     * Rename Layout, Element, Cell, Plugin to layout, element, cell, plugin
     * respectively.
     *
     * @param string $path Path.
     * @return void
     */
    protected function renameSubFolders(string $path): void
    {
        $dirIter = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::UNIX_PATHS
        );
        $iterIter = new RecursiveIteratorIterator($dirIter);

        $folders = ['Layout', 'Element', 'Cell', 'Email', 'Plugin', 'Flash'];

        foreach ($folders as $folder) {
            $templateDirs = new RegexIterator(
                $iterIter,
                '#/' . $folder . '/\.$#',
                RecursiveRegexIterator::SPLIT
            );

            foreach ($templateDirs as $key => $val) {
                $this->rename(
                    $val[0] . '/' . $folder,
                    $val[0] . '/' . strtolower($folder)
                );
            }
        }
    }

    /**
     * Recursively change template extension to .php
     *
     * @param string $path Path
     * @return void
     */
    protected function changeExt(string $path): void
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
    protected function rename(string $source, string $dest): void
    {
        $this->io->out("Move $source to $dest");

        if ($this->args->getOption('dry-run')) {
            return;
        }

        $parent = dirname($dest);
        if (!is_dir($parent)) {
            $old = umask(0);
            mkdir($parent, 0755, true);
            umask($old);
        }

        if ($this->git) {
            exec("git mv $source $dest");
        } else {
            rename($source, $dest);
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
            ->addArgument('type', [
                'choices' => ['templates', 'locales'],
                'required' => true,
                'help' => 'Specify file type to move.',
            ])
            ->addOption('path', [
                'help' => 'App/plugin path',
            ])
            ->addOption('dry-run', [
                'help' => 'Dry run.',
                'boolean' => true,
            ]);

        return $parser;
    }
}
