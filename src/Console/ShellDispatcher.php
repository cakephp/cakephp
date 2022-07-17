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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\MissingShellException;
use Cake\Console\Exception\StopException;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\Shell\Task\CommandTask;
use Cake\Utility\Inflector;

/**
 * Shell dispatcher handles dispatching CLI commands.
 *
 * Consult /bin/cake.php for how this class is used in practice.
 *
 * @deprecated 3.6.0 ShellDispatcher and Shell will be removed in 5.0
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
     * @var array<string, string>
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
    public function __construct(array $args = [], bool $bootstrap = true)
    {
        set_time_limit(0);
        $this->args = $args;

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
     * @return string|null The aliased class name, or null if the alias does not exist
     */
    public static function alias(string $short, ?string $original = null): ?string
    {
        $short = Inflector::camelize($short);
        if ($original) {
            static::$_aliases[$short] = $original;
        }

        return static::$_aliases[$short] ?? null;
    }

    /**
     * Clear any aliases that have been set.
     *
     * @return void
     */
    public static function resetAliases(): void
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
    public static function run(array $argv, array $extra = []): int
    {
        $dispatcher = new ShellDispatcher($argv);

        return $dispatcher->dispatch($extra);
    }

    /**
     * Defines current working environment.
     *
     * @return void
     * @throws \Cake\Core\Exception\CakeException
     */
    protected function _initEnvironment(): void
    {
        $this->_bootstrap();

        if (function_exists('ini_set')) {
            ini_set('html_errors', '0');
            ini_set('implicit_flush', '1');
            ini_set('max_execution_time', '0');
        }

        $this->shiftArgs();
    }

    /**
     * Initializes the environment and loads the CakePHP core.
     *
     * @return void
     */
    protected function _bootstrap()
    {
        if (!Configure::read('App.fullBaseUrl')) {
            Configure::write('App.fullBaseUrl', 'http://localhost');
        }
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
     *
     * - `requested` : if used, will prevent the Shell welcome message to be displayed
     * @return int The CLI command exit code. 0 is success.
     */
    public function dispatch(array $extra = []): int
    {
        try {
            $result = $this->_dispatch($extra);
        } catch (StopException $e) {
            return $e->getCode();
        }
        if ($result === null || $result === true) {
            /** @psalm-suppress DeprecatedClass */
            return Shell::CODE_SUCCESS;
        }
        if (is_int($result)) {
            return $result;
        }

        /** @psalm-suppress DeprecatedClass */
        return Shell::CODE_ERROR;
    }

    /**
     * Dispatch a request.
     *
     * @param array $extra Extra parameters that you can manually pass to the Shell
     * to be dispatched.
     * Built-in extra parameter is :
     *
     * - `requested` : if used, will prevent the Shell welcome message to be displayed
     * @return int|bool|null
     * @throws \Cake\Console\Exception\MissingShellMethodException
     */
    protected function _dispatch(array $extra = [])
    {
        $shellName = $this->shiftArgs();

        if (!$shellName) {
            $this->help();

            return false;
        }
        if (in_array($shellName, ['help', '--help', '-h'], true)) {
            $this->help();

            return true;
        }
        if (in_array($shellName, ['version', '--version'], true)) {
            $this->version();

            return true;
        }

        $shell = $this->findShell($shellName);

        $shell->initialize();

        return $shell->runCommand($this->args, true, $extra);
    }

    /**
     * For all loaded plugins, add a short alias
     *
     * This permits a plugin which implements a shell of the same name to be accessed
     * Using the shell name alone
     *
     * @return array the resultant list of aliases
     */
    public function addShortPluginAliases(): array
    {
        $plugins = Plugin::loaded();

        $io = new ConsoleIo();
        $task = new CommandTask($io);
        $io->setLoggers(false);
        $list = $task->getShellList() + ['app' => []];
        $fixed = array_flip($list['app']) + array_flip($list['CORE']);
        $aliases = $others = [];

        foreach ($plugins as $plugin) {
            if (!isset($list[$plugin])) {
                continue;
            }

            foreach ($list[$plugin] as $shell) {
                $aliases += [$shell => $plugin];
                if (!isset($others[$shell])) {
                    $others[$shell] = [$plugin];
                } else {
                    $others[$shell] = array_merge($others[$shell], [$plugin]);
                }
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
                if ($other !== $plugin) {
                    Log::write(
                        'debug',
                        "command '$shell' in plugin '$plugin' was not aliased, conflicts with '$other'",
                        ['shell-dispatcher']
                    );
                }
                continue;
            }

            if (isset($others[$shell])) {
                $conflicts = array_diff($others[$shell], [$plugin]);
                if (count($conflicts) > 0) {
                    $conflictList = implode("', '", $conflicts);
                    Log::write(
                        'debug',
                        "command '$shell' in plugin '$plugin' was not aliased, conflicts with '$conflictList'",
                        ['shell-dispatcher']
                    );
                }
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
    public function findShell(string $shell): Shell
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
    protected function _handleAlias(string $shell): string
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
     * @return string|null Either the classname or null.
     */
    protected function _shellExists(string $shell): ?string
    {
        $class = App::className($shell, 'Shell', 'Shell');
        if ($class) {
            return $class;
        }

        return null;
    }

    /**
     * Create the given shell name, and set the plugin property
     *
     * @param string $className The class name to instantiate
     * @param string $shortName The plugin-prefixed shell name
     * @return \Cake\Console\Shell A shell instance.
     */
    protected function _createShell(string $className, string $shortName): Shell
    {
        [$plugin] = pluginSplit($shortName);
        /** @var \Cake\Console\Shell $instance */
        $instance = new $className();
        $instance->plugin = trim((string)$plugin, '.');

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
    public function help(): void
    {
        trigger_error(
            'Console help cannot be generated from Shell classes anymore. ' .
            'Upgrade your application to use Cake\Console\CommandRunner instead.',
            E_USER_WARNING
        );
    }

    /**
     * Prints the currently installed version of CakePHP. Performs an internal dispatch to the CommandList Shell
     *
     * @return void
     */
    public function version(): void
    {
        trigger_error(
            'Version information cannot be generated from Shell classes anymore. ' .
            'Upgrade your application to use Cake\Console\CommandRunner instead.',
            E_USER_WARNING
        );
    }
}
