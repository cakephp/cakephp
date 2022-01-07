<?php
declare(strict_types=1);

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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Error\PhpError;
use Cake\TestSuite\TestCase;

class PhpErrorTest extends TestCase
{
    public function testBasicGetters()
    {
        $error = new PhpError(E_ERROR, 'something bad');
        $this->assertEquals(E_ERROR, $error->getCode());
        $this->assertEquals('something bad', $error->getMessage());
        $this->assertNull($error->getFile());
        $this->assertNull($error->getLine());
        $this->assertEquals([], $error->getTrace());
        $this->assertEquals('', $error->getTraceAsString());
    }

    public static function errorCodeProvider(): array
    {
        // [php error code, label, log-level]
        return [
            [E_ERROR, 'error', LOG_ERR],
            [E_WARNING, 'warning', LOG_WARNING],
            [E_NOTICE, 'notice', LOG_NOTICE],
            [E_STRICT, 'strict', LOG_NOTICE],
            [E_STRICT, 'strict', LOG_NOTICE],
            [E_USER_DEPRECATED, 'deprecated', LOG_NOTICE],
        ];
    }

    /**
     * @dataProvider errorCodeProvider
     */
    public function testMappings($phpCode, $label, $logLevel)
    {
        $error = new PhpError($phpCode, 'something bad');
        $this->assertEquals($phpCode, $error->getCode());
        $this->assertEquals($label, $error->getLabel());
        $this->assertEquals($logLevel, $error->getLogLevel());
    }

    public function testGetTraceAsString()
    {
        $trace = [
            ['file' => 'a.php', 'line' => 10, 'reference' => 'TestObject::a()'],
            ['file' => 'b.php', 'line' => 5, 'reference' => '[main]'],
        ];
        $error = new PhpError(E_ERROR, 'something bad', __FILE__, __LINE__, $trace);
        $this->assertEquals($trace, $error->getTrace());
        $expected = [
            'TestObject::a() a.php, line 10',
            '[main] b.php, line 5',
        ];
        $this->assertEquals(implode("\n", $expected), $error->getTraceAsString());
        $this->assertEquals('error', $error->getLabel());
    }
}
