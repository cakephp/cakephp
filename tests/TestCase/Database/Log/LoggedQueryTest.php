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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Log;

use Cake\Database\Log\LoggedQuery;
use Cake\TestSuite\TestCase;

/**
 * Tests LoggedQuery class
 */
class LoggedQueryTest extends TestCase
{

    /**
     * Tests that LoggedQuery can be converted to string
     *
     * @return void
     */
    public function testStringConversion()
    {
        $logged = new LoggedQuery;
        $logged->query = 'SELECT foo FROM bar';
        $this->assertEquals('duration=0 rows=0 SELECT foo FROM bar', (string)$logged);
    }
}
