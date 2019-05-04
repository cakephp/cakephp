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
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use ReflectionClass;
use ReflectionMethod;

/**
 * Base class for Shell Command reflection.
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

        $appPath = App::path('Shell');
        $shellList = $this->_findShells($shellList, $appPath[0], 'app', $skipFiles);

        $appPath = App::path('Command');
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
     * @param array $skip A list of commands to exclude.
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

    /**
     * Return a list of all commands
     *
     * @return array
     */
    public function commands(): array
    {
        $shellList = $this->getShellList();
        $flatten = Hash::flatten($shellList);
        $duplicates = array_intersect($flatten, array_unique(array_diff_key($flatten, array_unique($flatten))));
        $duplicates = Hash::expand($duplicates);

        $options = [];
        foreach ($shellList as $type => $commands) {
            foreach ($commands as $shell) {
                $prefix = '';
                if (!in_array(strtolower($type), ['app', 'core'], true) &&
                    isset($duplicates[$type]) &&
                    in_array($shell, $duplicates[$type], true)
                ) {
                    $prefix = $type . '.';
                }

                $options[] = $prefix . $shell;
            }
        }

        return $options;
    }

    /**
     * Return a list of subcommands for a given command
     *
     * @param string $commandName The command you want subcommands from.
     * @return string[]
     * @throws \ReflectionException
     */
    public function subCommands(string $commandName): array
    {
        $shell = $this->getShell($commandName);

        if (!$shell) {
            return [];
        }

        $taskMap = $this->Tasks->normalizeArray((array)$shell->tasks);
        $return = array_keys($taskMap);
        $return = array_map('Cake\Utility\Inflector::underscore', $return);

        $shellMethodNames = ['main', 'help', 'getOptionParser', 'initialize', 'runCommand'];

        $baseClasses = ['Object', 'Shell', 'AppShell'];

        $Reflection = new ReflectionClass($shell);
        $methods = $Reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $methodNames = [];
        foreach ($methods as $method) {
            $declaringClass = $method->getDeclaringClass()->getShortName();
            if (!in_array($declaringClass, $baseClasses, true)) {
                $methodNames[] = $method->getName();
            }
        }

        $return = array_merge($return, array_diff($methodNames, $shellMethodNames));
        sort($return);

        return $return;
    }

    /**
     * Get Shell instance for the given command
     *
     * @param string $commandName The command you want.
     * @return \Cake\Console\Shell|false Shell instance if the command can be found, false otherwise.
     */
    public function getShell(string $commandName)
    {
        [$pluginDot, $name] = pluginSplit($commandName, true);

        if (in_array(strtolower((string)$pluginDot), ['app.', 'core.'], true)) {
            $commandName = $name;
            $pluginDot = '';
        }

        if (!in_array($commandName, $this->commands(), true)
            && empty($pluginDot)
            && !in_array($name, $this->commands(), true)
        ) {
            return false;
        }

        if (empty($pluginDot)) {
            $shellList = $this->getShellList();

            if (!in_array($commandName, $shellList['app']) && !in_array($commandName, $shellList['CORE'], true)) {
                unset($shellList['CORE'], $shellList['app']);
                foreach ($shellList as $plugin => $commands) {
                    if (in_array($commandName, $commands, true)) {
                        $pluginDot = $plugin . '.';
                        break;
                    }
                }
            }
        }

        $name = Inflector::camelize($name);
        $pluginDot = Inflector::camelize((string)$pluginDot);
        $class = App::className($pluginDot . $name, 'Shell', 'Shell');
        if (!$class) {
            return false;
        }

        /** @var \Cake\Console\Shell $shell */
        $shell = new $class();
        $shell->plugin = trim($pluginDot, '.');
        $shell->initialize();

        return $shell;
    }

    /**
     * Get options list for the given command or subcommand
     *
     * @param string $commandName The command to get options for.
     * @param string $subCommandName The subcommand to get options for. Can be empty to get options for the command.
     * If this parameter is used, the subcommand must be a valid subcommand of the command passed
     * @return array Options list for the given command or subcommand
     */
    public function options(string $commandName, string $subCommandName = ''): array
    {
        $shell = $this->getShell($commandName);

        if (!$shell) {
            return [];
        }

        $parser = $shell->getOptionParser();

        if (!empty($subCommandName)) {
            $subCommandName = Inflector::camelize($subCommandName);
            if ($shell->hasTask($subCommandName)) {
                $parser = $shell->{$subCommandName}->getOptionParser();
            } else {
                return [];
            }
        }

        $options = [];
        $array = $parser->options();
        /** @var \Cake\Console\ConsoleInputOption $obj */
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
