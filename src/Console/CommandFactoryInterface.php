<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

/**
 * An interface for abstracting creation of command and shell instances.
 */
interface CommandFactoryInterface
{
    /**
     * The factory method for creating Command and Shell instances.
     *
     * @param string $className Command/Shell class name.
     * @return \Cake\Console\Shell|\Cake\Console\Command
     */
    public function create($className);
}
