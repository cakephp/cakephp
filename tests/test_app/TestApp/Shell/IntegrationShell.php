<?php
/**
 * IntegrationShell file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * IntegrationShell
 */
namespace TestApp\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;

class IntegrationShell extends Shell
{

    /**
     * Option parser
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = new ConsoleOptionParser();
        $argAndOptionParser = (new ConsoleOptionParser())
            ->addArgument('arg', [
                'required' => true
            ])
            ->addOption('opt', [
                'short' => 'o'
            ]);

        $parser
            ->addSubcommand('argsAndOptions', [
                'parser' => $argAndOptionParser
            ])
            ->addSubcommand('bridge');

        return $parser;
    }

    /**
     * Bridge of Death question
     *
     * @return void
     */
    public function bridge()
    {
        $name = $this->in('What is your name');

        if ($name !== 'cake') {
            $this->err('No!');
            $this->_stop(Shell::CODE_ERROR);
        }

        $color = $this->in('What is your favorite color?');

        if ($color !== 'blue') {
            $this->err('Wrong! <blink>Aaaahh</blink>');
            $this->_stop(Shell::CODE_ERROR);
        }

        $this->out('You may pass.');
    }

    /**
     * A sub command that requires an argument and has an option
     *
     * @return void
     */
    public function argsAndOptions()
    {
        $this->out('arg: ' . $this->args[0]);
        $this->out('opt: ' . $this->param('opt'));
    }
}
