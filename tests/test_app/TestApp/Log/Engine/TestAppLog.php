<?php
declare(strict_types=1);

/**
 * Test Suite Test App Logging stream class.
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
 * @since         1.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Log\Engine;

use Cake\Log\Engine\BaseLog;

/**
 * Test Suite Test App Logging stream class.
 */
class TestAppLog extends BaseLog
{
    /**
     * @var array|null
     */
    public $passedScope;

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->passedScope = $context;
    }
}
