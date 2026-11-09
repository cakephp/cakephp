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

use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;
use Cake\Core\Configure;
use function Cake\Core\env;

/**
 * built-in Server command
 */
#[AsCommand(
    'server',
    description: "Start PHP's built-in server or FrankenPHP for CakePHP",
    help: "<warning>[WARN] Don't use this in a production environment</warning>",
)]
#[Option('host', short: 'H', help: 'ServerHost')]
#[Option('port', short: 'p', help: 'ListenPort')]
#[Option('ini_path', short: 'I', help: 'php.ini path')]
#[Option('document_root', short: 'd', help: 'DocumentRoot')]
#[Option('frankenphp', short: 'f', help: "Use frankenphp instead of PHP's built-in server", boolean: true)]
class ServerCommand extends Command
{
    /**
     * Default ServerHost
     *
     * @var string
     */
    public const string DEFAULT_HOST = 'localhost';

    /**
     * Default ListenPort
     *
     * @var int
     */
    public const int DEFAULT_PORT = 8765;

    /**
     * server host
     *
     * @var string
     */
    protected string $host = self::DEFAULT_HOST;

    /**
     * listen port
     *
     * @var int
     */
    protected int $port = self::DEFAULT_PORT;

    /**
     * document root
     *
     * @var string
     */
    protected string $documentRoot = WWW_ROOT;

    /**
     * ini path
     *
     * @var string
     */
    protected string $iniPath = '';

    /**
     * The server type.
     *
     * @var string
     */
    protected string $server = 'php';

    /**
     * Starts up the Command and displays the welcome message.
     * Allows for checking and configuring prior to command or main execution
     *
     * @return void
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#lifecycle-callbacks
     */
    protected function startup(): void
    {
        if ($this->args->getOption('host')) {
            $this->host = (string)$this->args->getOption('host');
        }
        if ($this->args->getOption('port')) {
            $this->port = (int)$this->args->getOption('port');
        }
        if ($this->args->getOption('document_root')) {
            $this->documentRoot = (string)$this->args->getOption('document_root');
        }
        if ($this->args->getOption('ini_path')) {
            $this->iniPath = (string)$this->args->getOption('ini_path');
        }
        if ($this->args->getOption('frankenphp')) {
            $this->server = 'frankenphp';
        }

        // For Windows
        if (substr($this->documentRoot, -1, 1) === DIRECTORY_SEPARATOR) {
            $this->documentRoot = substr($this->documentRoot, 0, strlen($this->documentRoot) - 1);
        }
        if (preg_match("/^([a-z]:)[\\\]+(.+)$/i", $this->documentRoot, $m)) {
            $this->documentRoot = $m[1] . '\\' . $m[2];
        }

        $this->iniPath = rtrim($this->iniPath, DIRECTORY_SEPARATOR);
        if (preg_match("/^([a-z]:)[\\\]+(.+)$/i", $this->iniPath, $m)) {
            $this->iniPath = $m[1] . '\\' . $m[2];
        }

        $this->io->out();
        $this->io->out(sprintf('<info>Welcome to CakePHP %s Console</info>', 'v' . Configure::version()));
        $this->io->hr();
        $this->io->out(sprintf('App : %s', Configure::read('App.dir')));
        $this->io->out(sprintf('Path: %s', APP));
        $this->io->out(sprintf('DocumentRoot: %s', $this->documentRoot));
        $this->io->out(sprintf('Ini Path: %s', $this->iniPath));
        $this->io->hr();
    }

    /**
     * Execute.
     *
     * @return int The exit code
     */
    public function execute(): int
    {
        $this->startup();

        $this->io->out(sprintf(
            '%s server is running at http://%s:%s/',
            $this->server,
            $this->host,
            $this->port,
        ));
        $this->io->out('You can exit with <info>`CTRL-C`</info>');

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
            $this->host,
            $this->port,
            escapeshellarg($this->documentRoot),
        );

        if ($this->iniPath) {
            $command = sprintf('%s -c %s', $command, $this->iniPath);
        }

        return sprintf('%s %s', $command, escapeshellarg($this->documentRoot . '/index.php'));
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
            $this->host,
            $this->port,
            escapeshellarg($this->documentRoot),
        );
    }
}
