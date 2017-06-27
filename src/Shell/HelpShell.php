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
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Shell;

use Cake\Console\CommandCollection;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use SimpleXmlElement;

/**
 * Print out command list
 */
class HelpShell extends Shell
{
    protected $commands;

    /**
     * startup
     *
     * @return void
     */
    public function startup()
    {
        if (!$this->param('xml')) {
            parent::startup();
        }
    }

    /**
     * Set the command collection being used.
     *
     * @param \Cake\Console\CommandCollection $commands The commands to use.
     * @return void
     */
    public function setCommandCollection(CommandCollection $commands)
    {
        $this->commands = $commands;
    }

    /**
     * Main function Prints out the list of shells.
     *
     * @return void
     */
    public function main()
    {
        if (!$this->param('xml')) {
            $this->out('<info>Current Paths:</info>', 2);
            $this->out('* app:  ' . APP_DIR);
            $this->out('* root: ' . rtrim(ROOT, DIRECTORY_SEPARATOR));
            $this->out('* core: ' . rtrim(CORE_PATH, DIRECTORY_SEPARATOR));
            $this->out('');

            $this->out('<info>Available Commands:</info>', 2);
        }

        if (!$this->commands) {
            $this->err('Could not print command list, no CommandCollection was set using setCommandCollection()');
            return;
        }

        if ($this->param('xml')) {
            $this->asXml($this->commands);
            return;
        }
        $this->asText($this->commands);
    }

    /**
     * Output text.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to output.
     * @return void
     */
    protected function asText($commands)
    {
        foreach ($commands as $name => $class) {
            $this->out('- ' . $name);
        }
        $this->out('');

        $this->out('To run a command, type <info>`cake shell_name [args|options]`</info>');
        $this->out('To get help on a specific command, type <info>`cake shell_name --help`</info>', 2);
    }

    /**
     * Output as XML
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to output
     * @return void
     */
    protected function asXml($commands)
    {
        $shells = new SimpleXmlElement('<shells></shells>');
        foreach ($commands as $name => $class) {
            $shell = $shells->addChild('shell');
            $shell->addAttribute('name', $name);
            $shell->addAttribute('call_as', $name);
            $shell->addAttribute('provider', $class);
            $shell->addAttribute('help', $name . ' -h');
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
            'Get the list of available shells for this application.'
        )->addOption('xml', [
            'help' => 'Get the listing as XML.',
            'boolean' => true
        ]);

        return $parser;
    }
}
