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
 * @link          https://cakephp.org CakePHP Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell;

use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Inflector;
use SimpleXmlElement;

/**
 * Shows a list of commands available from the console.
 *
 * @property \Cake\Shell\Task\CommandTask $Command
 * @deprecated 3.5.0 Replaced by Cake\Shell\HelpShell
 */
class CommandListShell extends Shell
{

    /**
     * Contains tasks to load and instantiate
     *
     * @var array
     */
    public $tasks = ['Command'];

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
        $this->out(sprintf('PHP : %s', phpversion()));
        $this->hr();
    }

    /**
     * startup
     *
     * @return void
     */
    public function startup()
    {
        if (!$this->param('xml') && !$this->param('version')) {
            parent::startup();
        }
    }

    /**
     * Main function Prints out the list of shells.
     *
     * @return void
     */
    public function main()
    {
        if (!$this->param('xml') && !$this->param('version')) {
            $this->out('<info>Current Paths:</info>', 2);
            $this->out('* app:  ' . APP_DIR);
            $this->out('* root: ' . rtrim(ROOT, DIRECTORY_SEPARATOR));
            $this->out('* core: ' . rtrim(CORE_PATH, DIRECTORY_SEPARATOR));
            $this->out('');

            $this->out('<info>Available Shells:</info>', 2);
        }

        if ($this->param('version')) {
            $this->out(Configure::version());

            return;
        }

        $shellList = $this->Command->getShellList();
        if (!$shellList) {
            return;
        }

        if (!$this->param('xml')) {
            $this->_asText($shellList);
        } else {
            $this->_asXml($shellList);
        }
    }

    /**
     * Output text.
     *
     * @param array $shellList The shell list.
     * @return void
     */
    protected function _asText($shellList)
    {
        foreach ($shellList as $plugin => $commands) {
            sort($commands);
            $this->out(sprintf('[<info>%s</info>] %s', $plugin, implode(', ', $commands)));
            $this->out();
        }

        $this->out('To run an app or core command, type <info>`cake shell_name [args]`</info>');
        $this->out('To run a plugin command, type <info>`cake Plugin.shell_name [args]`</info>');
        $this->out('To get help on a specific command, type <info>`cake shell_name --help`</info>', 2);
    }

    /**
     * Output as XML
     *
     * @param array $shellList The shell list.
     * @return void
     */
    protected function _asXml($shellList)
    {
        $plugins = Plugin::loaded();
        $shells = new SimpleXmlElement('<shells></shells>');
        foreach ($shellList as $plugin => $commands) {
            foreach ($commands as $command) {
                $callable = $command;
                if (in_array($plugin, $plugins)) {
                    $callable = Inflector::camelize($plugin) . '.' . $command;
                }

                $shell = $shells->addChild('shell');
                $shell->addAttribute('name', $command);
                $shell->addAttribute('call_as', $callable);
                $shell->addAttribute('provider', $plugin);
                $shell->addAttribute('help', $callable . ' -h');
            }
        }
        $this->_io->setOutputAs(ConsoleOutput::RAW);
        $this->out($shells->saveXML());
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->setDescription(
            'Get the list of available shells for this CakePHP application.'
        )->addOption('xml', [
            'help' => 'Get the listing as XML.',
            'boolean' => true
        ])->addOption('version', [
            'help' => 'Prints the currently installed version of CakePHP. (deprecated - use `cake --version` instead)',
            'boolean' => true
        ]);

        return $parser;
    }
}
