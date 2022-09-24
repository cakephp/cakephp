<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
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
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = new ConsoleOptionParser();
        $argAndOptionParser = (new ConsoleOptionParser())
            ->addArgument('arg', [
                'required' => true,
            ])
            ->addOption('opt', [
                'short' => 'o',
            ]);

        $parser
            ->addSubcommand('argsAndOptions', [
                'parser' => $argAndOptionParser,
            ])
            ->addSubcommand('bridge')
            ->addSubcommand('abort_shell');

        return $parser;
    }

    /**
     * Bridge of Death question
     */
    public function bridge(): void
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
     */
    public function argsAndOptions(): void
    {
        $this->out('arg: ' . $this->args[0]);
        $this->out('opt: ' . $this->param('opt'));
    }

    /**
     * @throws \Cake\Console\Exception\StopException
     */
    public function abortShell(): void
    {
        $this->abort('Shell aborted');
    }
}
