<?php
/**
 * Test Suite Test App Logging stream class.
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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Log\Engine;

use Cake\Log\Engine\BaseLog;

/**
 * Test Suite Test App Logging stream class.
 *
 */
class TestAppLog extends BaseLog
{

    public $passedScope = null;

    public function log($level, $message, array $context = [])
    {
        $this->passedScope = $context;
    }
}
