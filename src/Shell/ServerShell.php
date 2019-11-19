<?php
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

namespace Cake\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;

/**
 * built-in Server Shell
 */
class ServerShell extends Shell
{
    /**
     * Default ServerHost
     *
     * @var string
     */
    const DEFAULT_HOST = 'localhost';

    /**
     * Default ListenPort
     *
     * @var int
     */
    const DEFAULT_PORT = 8765;

    /**
     * server host
     *
     * @var string
     */
    protected $_host = self::DEFAULT_HOST;

    /**
     * listen port
     *
     * @var int
     */
    protected $_port = self::DEFAULT_PORT;

    /**
     * document root
     *
     * @var string
     */
    protected $_documentRoot = WWW_ROOT;

    /**
     * ini path
     *
     * @var string
     */
    protected $_iniPath = '';

    /**
     * Starts up the Shell and displays the welcome message.
     * Allows for checking and configuring prior to command or main execution
     *
     * Override this method if you want to remove the welcome information,
     * or otherwise modify the pre-command flow.
     *
     * @return void
     * @link https://book.cakephp.org/3/en/console-and-shells.html#hook-methods
     */
    public function startup()
    {
        if ($this->param('host')) {
            $this->_host = $this->param('host');
        }
        if ($this->param('port')) {
            $this->_port = (int)$this->param('port');
        }
        if ($this->param('document_root')) {
            $this->_documentRoot = $this->param('document_root');
        }
        if ($this->param('ini_path')) {
            $this->_iniPath = $this->param('ini_path');
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

        parent::startup();
    }

    /**
     * Displays a header for the shell
     *
     * @return void
     */
    protected function _welcome()
    {
        $this->out();
        $this->out(sprintf('<info>Welcome to CakePHP %s Console</info>', 'v' . Configure::version()));
        $this->hr();
        $this->out(sprintf('App : %s', APP_DIR));
        $this->out(sprintf('Path: %s', APP));
        $this->out(sprintf('DocumentRoot: %s', $this->_documentRoot));
        $this->out(sprintf('Ini Path: %s', $this->_iniPath));
        $this->hr();
    }

    /**
     * Override main() to handle action
     *
     * @return void
     */
    public function main()
    {
        $command = sprintf(
            'php -S %s:%d -t %s',
            $this->_host,
            $this->_port,
            escapeshellarg($this->_documentRoot)
        );

        if (!empty($this->_iniPath)) {
            $command = sprintf('%s -c %s', $command, $this->_iniPath);
        }

        $command = sprintf('%s %s', $command, escapeshellarg($this->_documentRoot . '/index.php'));

        $port = ':' . $this->_port;
        $this->out(sprintf('built-in server is running in http://%s%s/', $this->_host, $port));
        $this->out(sprintf('You can exit with <info>`CTRL-C`</info>'));
        system($command);
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->setDescription([
            'PHP Built-in Server for CakePHP',
            '<warning>[WARN] Don\'t use this in a production environment</warning>',
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
        ]);

        return $parser;
    }
}
