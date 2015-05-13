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
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\ConsoleIo;

/**
 * Base class for Macros.
 *
 * Console Macros allow you to package up reusable blocks
 * of Console output logic. For example creating tables,
 * progress bars or ascii art.
 */
abstract class Macro
{
    public function __construct(ConsoleIo $io)
    {
        $this->_io = $io;
    }

    /**
     * This method should output content using `$this->_io`.
     *
     * @param array $args The arguments for the macro.
     * @return void
     */
    abstract public function output($args);
}
