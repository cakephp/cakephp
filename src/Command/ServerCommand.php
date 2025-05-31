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
 * @since         2.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use function Cake\Core\env;

/**
 * built-in Server command
 */
class ServerCommand extends Command
{
    /**
     * Default ServerHost
     *
     * @var string
     */
    public const DEFAULT_HOST = 'localhost';

    /**
     * Default ListenPort
     *
     * @var int
     */
    public const DEFAULT_PORT = 8765;

    /**
     * server host
     *
     * @var string
     */
    protected string $_host = self::DEFAULT_HOST;

    /**
     * listen port
     *
     * @var int
     */
    protected int $_port = self::DEFAULT_PORT;

    /**
     * document root
     *
     * @var string
     */
    protected string $_documentRoot = WWW_ROOT;

    /**
     * ini path
     *
     * @var string
     */
    protected string $_iniPath = '';

    /**
     * The server type.
     *
     * @var string
     */
    protected string $server = 'php';

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return "Start PHP's built-in server or FrankenPHP for CakePHP";
    }

    /**
     * Starts up the Command and displays the welcome message.
     * Allows for checking and configuring prior to command or main execution
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @link https://book.cakephp.org/5/en/console-and-shells.html#hook-methods
     */
    protected function startup(Arguments $args, ConsoleIo $io): void
    {
        if ($args->getOption('host')) {
            $this->_host = (string)$args->getOption('host');
        }
        if ($args->getOption('port')) {
            $this->_port = (int)$args->getOption('port');
        }
        if ($args->getOption('document_root')) {
            $this->_documentRoot = (string)$args->getOption('document_root');
        }
        if ($args->getOption('ini_path')) {
            $this->_iniPath = (string)$args->getOption('ini_path');
        }
        if ($args->getOption('frankenphp')) {
            $this->server = 'frankenphp';
        }

        // For Windows
        if (substr($this->_documentRoot, -1, 1) === DIRECTORY_SEPARATOR) {
            $this->_documentRoot = substr($this->_documentRoot, 0, strlen($this->_documentRoot) - 1);
        }
        if (preg_match("/^([a-z]:)[\\\]+(.+)$/i", $this->_documentRoot, $m)) {
            $this->_documentRoot = $m[1] . '\\' . $m[2];
        }

        $this->_iniPath = rtrim($this->_iniPath, DIRECTORY_SEPARATOR);
        if (preg_match("/^([a-z]:)[\\\]+(.+)$/i", $this->_iniPath, $m)) {
            $this->_iniPath = $m[1] . '\\' . $m[2];
        }

        $io->out();
        $io->out(sprintf('<info>Welcome to CakePHP %s Console</info>', 'v' . Configure::version()));
        $io->hr();
        $io->out(sprintf('App : %s', Configure::read('App.dir')));
        $io->out(sprintf('Path: %s', APP));
        $io->out(sprintf('DocumentRoot: %s', $this->_documentRoot));
        $io->out(sprintf('Ini Path: %s', $this->_iniPath));
        $io->hr();
    }

    /**
     * Execute.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int The exit code
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->startup($args, $io);

        $io->out(sprintf(
            '%s server is running at http://%s:%s/',
            $this->server,
            $this->_host,
            $this->_port,
        ));
        $io->out('You can exit with <info>`CTRL-C`</info>');

        return $this->runCommand($this->{$this->server . 'Command'}());
    }

    /**
     * Runs the command.
     *
     * @param string $command The command to run
     * @return int The exit code
     */
    protected function runCommand(string $command): int
    {
        if (system($command) === false) {
            return static::CODE_ERROR;
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Returns the command to run PHP's built-in server.
     *
     * @return string
     */
    protected function phpCommand(): string
    {
        $command = sprintf(
            '%s -S %s:%d -t %s',
            (string)env('PHP', 'php'),
            $this->_host,
            $this->_port,
            escapeshellarg($this->_documentRoot),
        );

        if ($this->_iniPath) {
            $command = sprintf('%s -c %s', $command, $this->_iniPath);
        }

        return sprintf('%s %s', $command, escapeshellarg($this->_documentRoot . '/index.php'));
    }

    /**
     * Returns the command to run frankenphp's server.
     *
     * @return string
     */
    protected function frankenphpCommand(): string
    {
        return sprintf(
            '%s php-server -a -l %s:%d -r %s',
            (string)env('FRANKENPHP', 'frankenphp'),
            $this->_host,
            $this->_port,
            escapeshellarg($this->_documentRoot),
        );
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription([
            static::getDescription(),
            "<warning>[WARN] Don't use this in a production environment</warning>",
        ])->addOption('host', [
            'short' => 'H',
            'help' => 'ServerHost',
        ])->addOption('port', [
            'short' => 'p',
            'help' => 'ListenPort',
        ])->addOption('ini_path', [
            'short' => 'I',
            'help' => 'php.ini path',
        ])->addOption('document_root', [
            'short' => 'd',
            'help' => 'DocumentRoot',
        ])->addOption('frankenphp', [
            'boolean' => true,
            'short' => 'f',
            'help' => "Use frankenphp instead of PHP's built-in server",
        ]);

        return $parser;
    }
}
