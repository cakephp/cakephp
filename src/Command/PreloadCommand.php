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
 * @since         4.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Preload\Preloader;
use SplFileInfo;

/**
 * Generates a preload file
 *
 * This can be included as part of your build process.
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */
class PreloadCommand extends Command
{
    /**
     * @var \Cake\Preload\Preloader
     */
    private $preloader;

    public function __construct()
    {
        parent::__construct();
        $this->preloader = new Preloader();
    }

    /**
     * Generates a preload file
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->cakephp();
        $this->packages($args);
        $this->applications($args);

        $result = $this->preloader->write($args->getOption('name'));

        if ($result) {
            $io->hr();
            $io->success('Preload written to ' . $args->getOption('name'));
            $io->out('You must restart your PHP service for the changes to take effect.');
            $io->hr();

            return static::CODE_SUCCESS;
        }

        $io->err('Error encountered writing to ' . $args->getOption('name'));

        return static::CODE_ERROR;
    }

    /**
     * Loads the CakePHP framework
     *
     * @return void
     */
    private function cakephp(): void
    {
        $ignorePaths = implode('|', ['src\/Console', 'src\/Command', 'src\/Shell', 'src\/TestSuite']);

        $this->preloader->loadPath(CAKE, function(SplFileInfo $file) use ($ignorePaths) {
            return !preg_match("/($ignorePaths)/", $file->getPathname());
        });
    }

    /**
     * Adds a list of vendor packages
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @return void
     */
    private function packages(Arguments $args): void
    {
        if (empty($args->getOption('packages'))) {
            return;
        }

        $packages = explode(',', $args->getOption('packages'));
        $packages = array_filter($packages, function ($package) {
            $path = ROOT . DS . 'vendor' . DS . $package;
            if (file_exists($path)) {
                return true;
            }
            triggerWarning("Package $package could not be located at $path");
        });

        $packages = array_map(function($package){
            return ROOT . DS . 'vendor' . DS . $package;
        }, $packages);

        foreach ($packages as $package) {
            $this->preloader->loadPath($package);
        }
    }

    /**
     * Adds the users APP and plugins into the preloader
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @return void
     */
    private function applications(Arguments $args): void
    {
        $apps = $args->getOption('app') ? [APP] : [];

        if ($args->getOption('plugins') === '*') {
            $apps[] = ROOT . DS . 'plugins';
        } else if (!empty($args->getOption('plugins'))) {
            foreach (explode(',', $args->getOption('plugins')) as $plugin) {
                $apps[] = ROOT . DS . 'plugins' . DS  . $plugin . DS . 'src';
            }
        }

        $ignorePaths = implode('|', ['src\/Console', 'src\/Command', 'tests\/']);

        foreach ($apps as $app) {
            $this->preloader->loadPath($app, function(SplFileInfo $file) use ($ignorePaths) {
                return !preg_match("/($ignorePaths)/", $file->getPathname());
            });
        }
    }

    /**
     * Get the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Generate a preload file')
            ->addOption('name', [
                'help' => 'The preload file path.',
                'default' => ROOT . DS . 'preload.php',
            ])
            ->addOption('app', [
                'help' => 'Add your applications src directory into the preloader',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('plugins', [
                'help' => 'A comma separated list of your plugins to load or `*` to load all plugins/*',
            ])
            ->addOption('packages', [
                'help' => 'A comma separated list of packages (e.g. vendor-name/package-name) to add to the preloader',
            ]);

        return $parser;
    }
}
