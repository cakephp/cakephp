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
 * @since         2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell\Task;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Utility\Inflector;
use ReflectionClass;
use ReflectionMethod;

/**
 * Base class for Shell Command reflection.
 *
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
        $skipFiles = ['AppShell'];
        $hiddenCommands = ['CommandListShell', 'CompletionShell'];

        $plugins = Plugin::loaded();
        $shellList = array_fill_keys($plugins, null) + ['CORE' => null, 'app' => null];

        $shells = $this->_scanDir(dirname(__DIR__));
        $shells = array_diff($shells, $skipFiles, $hiddenCommands);
        $shellList = $this->_appendShells('CORE', $shells, $shellList);

        $appPath = App::path('Shell');
        $appShells = $this->_scanDir($appPath[0]);
        $appShells = array_diff($appShells, $shells, $skipFiles);
        $shellList = $this->_appendShells('app', $appShells, $shellList);

        foreach ($plugins as $plugin) {
            $pluginPath = Plugin::classPath($plugin) . 'Shell';
            $pluginShells = $this->_scanDir($pluginPath);
            $shellList = $this->_appendShells($plugin, $pluginShells, $shellList);
        }

        return array_filter($shellList);
    }

    /**
     * Scan the provided paths for shells, and append them into $shellList
     *
     * @param string $type The type of object.
     * @param array $shells The shell name.
     * @param array $shellList List of shells.
     * @return array The updated $shellList
     */
    protected function _appendShells($type, $shells, $shellList)
    {
        foreach ($shells as $shell) {
            $shellList[$type][] = Inflector::underscore(str_replace('Shell', '', $shell));
        }
        return $shellList;
    }

    /**
     * Scan a directory for .php files and return the class names that
     * should be within them.
     *
     * @param string $dir The directory to read.
     * @return array The list of shell classnames based on conventions.
     */
    protected function _scanDir($dir)
    {
        $dir = new Folder($dir);
        $contents = $dir->read(true, true);
        if (empty($contents[1])) {
            return [];
        }
        $shells = [];
        foreach ($contents[1] as $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
            $shells[] = substr($file, 0, -4);
        }
        return $shells;
    }

    /**
     * Return a list of all commands
     *
     * @return array
     */
    public function commands()
    {
        $shellList = $this->getShellList();

        $options = [];
        foreach ($shellList as $type => $commands) {
            $prefix = '';
            if (!in_array(strtolower($type), ['app', 'core'])) {
                $prefix = $type . '.';
            }

            foreach ($commands as $shell) {
                $options[] = $prefix . $shell;
            }
        }

        return $options;
    }

    /**
     * Return a list of subcommands for a given command
     *
     * @param string $commandName The command you want subcommands from.
     * @return array
     */
    public function subCommands($commandName)
    {
        $Shell = $this->getShell($commandName);

        if (!$Shell) {
            return [];
        }

        $taskMap = $this->Tasks->normalizeArray((array)$Shell->tasks);
        $return = array_keys($taskMap);
        $return = array_map('Cake\Utility\Inflector::underscore', $return);

        $shellMethodNames = ['main', 'help', 'getOptionParser'];

        $baseClasses = ['Object', 'Shell', 'AppShell'];

        $Reflection = new ReflectionClass($Shell);
        $methods = $Reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $methodNames = [];
        foreach ($methods as $method) {
            $declaringClass = $method->getDeclaringClass()->getShortName();
            if (!in_array($declaringClass, $baseClasses)) {
                $methodNames[] = $method->getName();
            }
        }

        $return += array_diff($methodNames, $shellMethodNames);
        sort($return);

        return $return;
    }

    /**
     * Get Shell instance for the given command
     *
     * @param mixed $commandName The command you want.
     * @return mixed
     */
    public function getShell($commandName)
    {
        list($pluginDot, $name) = pluginSplit($commandName, true);

        if (in_array(strtolower($pluginDot), ['app.', 'core.'])) {
            $commandName = $name;
            $pluginDot = '';
        }

        if (!in_array($commandName, $this->commands())) {
            return false;
        }

        $name = Inflector::camelize($name);
        $pluginDot = Inflector::camelize($pluginDot);
        $class = App::className($pluginDot . $name, 'Shell', 'Shell');
        if (!$class) {
            return false;
        }

        $Shell = new $class();
        $Shell->plugin = trim($pluginDot, '.');
        $Shell->initialize();

        return $Shell;
    }

    /**
     * Get Shell instance for the given command
     *
     * @param mixed $commandName The command to get options for.
     * @return array
     */
    public function options($commandName)
    {
        $Shell = $this->getShell($commandName);
        if (!$Shell) {
            $parser = new ConsoleOptionParser();
        } else {
            $parser = $Shell->getOptionParser();
        }

        $options = [];
        $array = $parser->options();
        foreach ($array as $name => $obj) {
            $options[] = "--$name";
            $short = $obj->short();
            if ($short) {
                $options[] = "-$short";
            }
        }
        return $options;
    }
}
