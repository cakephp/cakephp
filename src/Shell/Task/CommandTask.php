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
 * @since         2.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Filesystem\Filesystem;
use Cake\Utility\Inflector;

/**
 * Base class for Shell Command reflection.
 *
 * @internal
 */
class CommandTask extends Shell
{
    /**
     * Gets the shell command listing.
     *
     * @return array
     */
    public function getShellList()
    {
        $skipFiles = ['app'];
        $hiddenCommands = ['command_list', 'completion'];
        $plugins = Plugin::loaded();
        $shellList = array_fill_keys($plugins, null) + ['CORE' => null, 'app' => null];

        $appPath = App::classPath('Shell');
        $shellList = $this->_findShells($shellList, $appPath[0], 'app', $skipFiles);

        $appPath = App::classPath('Command');
        $shellList = $this->_findShells($shellList, $appPath[0], 'app', $skipFiles);

        $skipCore = array_merge($skipFiles, $hiddenCommands, $shellList['app']);
        $corePath = dirname(__DIR__);
        $shellList = $this->_findShells($shellList, $corePath, 'CORE', $skipCore);

        $corePath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'Command';
        $shellList = $this->_findShells($shellList, $corePath, 'CORE', $skipCore);

        foreach ($plugins as $plugin) {
            $pluginPath = Plugin::classPath($plugin) . 'Shell';
            $shellList = $this->_findShells($shellList, $pluginPath, $plugin, []);
        }

        return array_filter($shellList);
    }

    /**
     * Find shells in $path and add them to $shellList
     *
     * @param array $shellList The shell listing array.
     * @param string $path The path to look in.
     * @param string $key The key to add shells to
     * @param string[] $skip A list of commands to exclude.
     * @return array The updated list of shells.
     */
    protected function _findShells(array $shellList, string $path, string $key, array $skip): array
    {
        $shells = $this->_scanDir($path);

        return $this->_appendShells($key, $shells, $shellList, $skip);
    }

    /**
     * Scan the provided paths for shells, and append them into $shellList
     *
     * @param string $type The type of object.
     * @param string[] $shells The shell names.
     * @param array $shellList List of shells.
     * @param string[] $skip List of command names to skip.
     * @return array The updated $shellList
     */
    protected function _appendShells(string $type, array $shells, array $shellList, array $skip): array
    {
        if (!isset($shellList[$type])) {
            $shellList[$type] = [];
        }

        foreach ($shells as $shell) {
            $name = Inflector::underscore(preg_replace('/(Shell|Command)$/', '', $shell));
            if (!in_array($name, $skip, true)) {
                $shellList[$type][] = $name;
            }
        }
        sort($shellList[$type]);

        return $shellList;
    }

    /**
     * Scan a directory for .php files and return the class names that
     * should be within them.
     *
     * @param string $dir The directory to read.
     * @return array The list of shell classnames based on conventions.
     */
    protected function _scanDir(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $fs = new Filesystem();
        $files = $fs->find($dir, '/\.php$/');

        $shells = [];
        foreach ($files as $file) {
            $shells[] = $file->getBasename('.php');
        }

        sort($shells);

        return $shells;
    }
}
