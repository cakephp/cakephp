<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Utility\Inflector;

/**
 * Used by CommandCollection and CommandTask to scan the filesystem
 * for command classes.
 *
 * @internal
 */
class CommandScanner
{
    /**
     * Scan CakePHP internals for shells & commands.
     *
     * @return array A list of command metadata.
     */
    public function scanCore()
    {
        $coreShells = $this->scanDir(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Shell' . DIRECTORY_SEPARATOR,
            'Cake\Shell\\',
            '',
            ['command_list']
        );
        $coreCommands = $this->scanDir(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Command' . DIRECTORY_SEPARATOR,
            'Cake\Command\\',
            '',
            ['command_list']
        );

        return array_merge($coreShells, $coreCommands);
    }

    /**
     * Scan the application for shells & commands.
     *
     * @return array A list of command metadata.
     */
    public function scanApp()
    {
        $appNamespace = Configure::read('App.namespace');
        $appShells = $this->scanDir(
            App::path('Shell')[0],
            $appNamespace . '\Shell\\',
            '',
            []
        );
        $appCommands = $this->scanDir(
            App::path('Command')[0],
            $appNamespace . '\Command\\',
            '',
            []
        );

        return array_merge($appShells, $appCommands);
    }

    /**
     * Scan the named plugin for shells and commands
     *
     * @param string $plugin The named plugin.
     * @return array A list of command metadata.
     */
    public function scanPlugin($plugin)
    {
        if (!Plugin::isLoaded($plugin)) {
            return [];
        }
        $path = Plugin::classPath($plugin);
        $namespace = str_replace('/', '\\', $plugin);
        $prefix = Inflector::underscore($plugin) . '.';

        $commands = $this->scanDir($path . 'Command', $namespace . '\Command\\', $prefix, []);
        $shells = $this->scanDir($path . 'Shell', $namespace . '\Shell\\', $prefix, []);

        return array_merge($shells, $commands);
    }

    /**
     * Scan a directory for .php files and return the class names that
     * should be within them.
     *
     * @param string $path The directory to read.
     * @param string $namespace The namespace the shells live in.
     * @param string $prefix The prefix to apply to commands for their full name.
     * @param string[] $hide A list of command names to hide as they are internal commands.
     * @return array The list of shell info arrays based on scanning the filesystem and inflection.
     */
    protected function scanDir($path, $namespace, $prefix, array $hide)
    {
        $dir = new Folder($path);
        $contents = $dir->read(true, true);
        if (empty($contents[1])) {
            return [];
        }

        $classPattern = '/(Shell|Command)$/';
        $shells = [];
        foreach ($contents[1] as $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
            $shell = substr($file, 0, -4);
            if (!preg_match($classPattern, $shell)) {
                continue;
            }

            $name = Inflector::underscore(preg_replace($classPattern, '', $shell));
            if (in_array($name, $hide, true)) {
                continue;
            }

            $class = $namespace . $shell;
            if (!is_subclass_of($class, Shell::class) && !is_subclass_of($class, Command::class)) {
                continue;
            }

            if (is_subclass_of($class, Command::class)) {
                $name = $class::defaultName();
            }
            $shells[] = [
                'file' => $path . $file,
                'fullName' => $prefix . $name,
                'name' => $name,
                'class' => $class,
            ];
        }

        return $shells;
    }
}
