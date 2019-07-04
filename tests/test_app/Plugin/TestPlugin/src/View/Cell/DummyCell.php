<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestPlugin\View\Cell;

/**
 * DummyCell class
 */
class DummyCell extends \Cake\View\Cell
{
    /**
     * Default cell action.
     *
     * @return void
     */
    public function display()
    {
    }

    /**
     * Simple echo.
     *
     * @param string $msg
     * @return void
     */
    public function echoThis($msg)
    {
        $this->set('msg', $msg);
    }
}
