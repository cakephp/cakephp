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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\MissingShellException;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\Shell\Task\CommandTask;
use Cake\Utility\Inflector;

/**
 * Shell dispatcher handles dispatching cli commands.
 *
 * Consult /bin/cake.php for how this class is used in practice.
 */
class ShellDispatcher
{

    /**
     * Contains arguments parsed from the command line.
     *
     * @var array
     */
    public $args = [];

    /**
     * List of connected aliases.
     *
     * @var array
     */
    protected static $_aliases = [];

    /**
     * Constructor
     *
     * The execution of the script is stopped after dispatching the request with
     * a status code of either 0 or 1 according to the result of the dispatch.
     *
     * @param array $args the argv from PHP
     * @param bool $bootstrap Should the environment be bootstrapped.
     */
    public function __construct($args = [], $bootstrap = true)
    {
        set_time_limit(0);
        $this->args = (array)$args;

        $this->addShortPluginAliases();

        if ($bootstrap) {
            $this->_initEnvironment();
        }
    }

    /**
     * Add an alias for a shell command.
     *
     * Aliases allow you to call shells by alternate names. This is most
     * useful when dealing with plugin shells that you want to have shorter
     * names for.
     *
     * If you re-use an alias the last alias set will be the one available.
     *
     * ### Usage
     *
     * Aliasing a shell named ClassName:
     *
     * ```
     * $this->alias('alias', 'ClassName');
     * ```
     *
     * Getting the original name for a given alias:
     *
     * ```
     * $this->alias('alias');
     * ```
     *
     * @param string $short The new short name for the shell.
     * @param string|null $original The original full name for the shell.
     * @return string|false The aliased class name, or false if the alias does not exist
     */
    public static function alias($short, $original = null)
    {
        $short = Inflector::camelize($short);
        if ($original) {
            static::$_aliases[$short] = $original;
        }

        return isset(static::$_aliases[$short]) ? static::$_aliases[$short] : false;
    }

    /**
     * Clear any aliases that have been set.
     *
     * @return void
     */
    public static function resetAliases()
    {
        static::$_aliases = [];
    }

    /**
     * Run the dispatcher
     *
     * @param array $argv The argv from PHP
     * @param array $extra Extra parameters
     * @return int The exit code of the shell process.
     */
    public static function run($argv, $extra = [])
    {
        $dispatcher = new ShellDispatcher($argv);
        return $dispatcher->dispatch($extra);
    }

    /**
     * Defines current working environment.
     *
     * @return void
     * @throws \Cake\Core\Exception\Exception
     */
    protected function _initEnvironment()
    {
        if (!$this->_bootstrap()) {
            $message = "Unable to load CakePHP core.\nMake sure Cake exists in " . CAKE_CORE_INCLUDE_PATH;
            throw new Exception($message);
        }

        if (function_exists('ini_set')) {
            ini_set('html_errors', false);
            ini_set('implicit_flush', true);
            ini_set('max_execution_time', 0);
        }

        $this->shiftArgs();
    }

    /**
     * Initializes the environment and loads the CakePHP core.
     *
     * @return bool Success.
     */
    protected function _bootstrap()
    {
        if (!Configure::read('App.fullBaseUrl')) {
            Configure::write('App.fullBaseUrl', 'http://localhost');
        }

        return true;
    }

    /**
     * Dispatches a CLI request
     *
     * Converts a shell command result into an exit code. Null/True
     * are treated as success. All other return values are an error.
     *
     * @param array $extra Extra parameters that you can manually pass to the Shell
     * to be dispatched.
     * Built-in extra parameter is :
     * - `requested` : if used, will prevent the Shell welcome message to be displayed
     * @return int The cli command exit code. 0 is success.
     */
    public function dispatch($extra = [])
    {
        $result = $this->_dispatch($extra);
        if ($result === null || $result === true) {
            return 0;
        }
        return 1;
    }

    /**
     * Dispatch a request.
     *
     * @param array $extra Extra parameters that you can manually pass to the Shell
     * to be dispatched.
     * Built-in extra parameter is :
     * - `requested` : if used, will prevent the Shell welcome message to be displayed
     * @return bool
     * @throws \Cake\Console\Exception\MissingShellMethodException
     */
    protected function _dispatch($extra = [])
    {
        $shell = $this->shiftArgs();

        if (!$shell) {
            $this->help();
            return false;
        }
        if (in_array($shell, ['help', '--help', '-h'])) {
            $this->help();
            return true;
        }

        $Shell = $this->findShell($shell);

        $Shell->initialize();
        return $Shell->runCommand($this->args, true, $extra);
    }

    /**
     * For all loaded plugins, add a short alias
     *
     * This permits a plugin which implements a shell of the same name to be accessed
     * Using the shell name alone
     *
     * @return array the resultant list of aliases
     */
    public function addShortPluginAliases()
    {
        $plugins = Plugin::loaded();

        $io = new ConsoleIo();
        $task = new CommandTask($io);
        $io->setLoggers(false);
        $list = $task->getShellList() + ['app' => []];
        $fixed = array_flip($list['app']) + array_flip($list['CORE']);
        $aliases = [];

        foreach ($plugins as $plugin) {
            if (!isset($list[$plugin])) {
                continue;
            }

            foreach ($list[$plugin] as $shell) {
                $aliases += [$shell => $plugin];
            }
        }

        foreach ($aliases as $shell => $plugin) {
            if (isset($fixed[$shell])) {
                Log::write(
                    'debug',
                    "command '$shell' in plugin '$plugin' was not aliased, conflicts with another shell",
                    ['shell-dispatcher']
                );
                continue;
            }

            $other = static::alias($shell);
            if ($other) {
                $other = $aliases[$shell];
                Log::write(
                    'debug',
                    "command '$shell' in plugin '$plugin' was not aliased, conflicts with '$other'",
                    ['shell-dispatcher']
                );
                continue;
            }

            static::alias($shell, "$plugin.$shell");
        }

        return static::$_aliases;
    }

    /**
     * Get shell to use, either plugin shell or application shell
     *
     * All paths in the loaded shell paths are searched, handles alias
     * dereferencing
     *
     * @param string $shell Optionally the name of a plugin
     * @return \Cake\Console\Shell A shell instance.
     * @throws \Cake\Console\Exception\MissingShellException when errors are encountered.
     */
    public function findShell($shell)
    {
        $className = $this->_shellExists($shell);
        if (!$className) {
            $shell = $this->_handleAlias($shell);
            $className = $this->_shellExists($shell);
        }

        if (!$className) {
            throw new MissingShellException([
                'class' => $shell,
            ]);
        }

        return $this->_createShell($className, $shell);
    }

    /**
     * If the input matches an alias, return the aliased shell name
     *
     * @param string $shell Optionally the name of a plugin or alias
     * @return string Shell name with plugin prefix
     */
    protected function _handleAlias($shell)
    {
        $aliased = static::alias($shell);
        if ($aliased) {
            $shell = $aliased;
        }

        $class = array_map('Cake\Utility\Inflector::camelize', explode('.', $shell));
        return implode('.', $class);
    }

    /**
     * Check if a shell class exists for the given name.
     *
     * @param string $shell The shell name to look for.
     * @return string|bool Either the classname or false.
     */
    protected function _shellExists($shell)
    {
        $class = App::className($shell, 'Shell', 'Shell');
        if (class_exists($class)) {
            return $class;
        }
        return false;
    }

    /**
     * Create the given shell name, and set the plugin property
     *
     * @param string $className The class name to instantiate
     * @param string $shortName The plugin-prefixed shell name
     * @return \Cake\Console\Shell A shell instance.
     */
    protected function _createShell($className, $shortName)
    {
        list($plugin) = pluginSplit($shortName);
        $instance = new $className();
        $instance->plugin = trim($plugin, '.');
        return $instance;
    }

    /**
     * Removes first argument and shifts other arguments up
     *
     * @return mixed Null if there are no arguments otherwise the shifted argument
     */
    public function shiftArgs()
    {
        return array_shift($this->args);
    }

    /**
     * Shows console help. Performs an internal dispatch to the CommandList Shell
     *
     * @return void
     */
    public function help()
    {
        $this->args = array_merge(['command_list'], $this->args);
        $this->dispatch();
    }
}
