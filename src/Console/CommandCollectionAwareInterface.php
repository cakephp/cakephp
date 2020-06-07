<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

/**
 * An interface for shells that take a CommandCollection
 * during initialization.
 */
interface CommandCollectionAwareInterface
{
    /**
     * Set the command collection being used.
     *
     * @param \Cake\Console\CommandCollection $commands The commands to use.
     * @return void
     */
    public function setCommandCollection(CommandCollection $commands);
}
