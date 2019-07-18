<?php
/**
 * SampleTask file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Shell\Task;

use Cake\Console\Shell;

class SampleTask extends Shell
{

    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addOption('sample', [
            'short' => 's',
            'help' => 'This is a sample option for the sample task.',
        ]);

        return $parser;
    }
}
