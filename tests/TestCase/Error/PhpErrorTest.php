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
use Iterator;

class PhpErrorTest extends TestCase
{
    public function testBasicGetters(): void
    {
        $error = new PhpError(E_ERROR, 'something bad');
        $this->assertSame(E_ERROR, $error->getCode());
        $this->assertSame('something bad', $error->getMessage());
        $this->assertNull($error->getFile());
        $this->assertNull($error->getLine());
        $this->assertSame([], $error->getTrace());
        $this->assertSame('', $error->getTraceAsString());
    }

    public static function errorCodeProvider(): Iterator
    {
        // [php error code, label, log-level]
        yield [E_ERROR, 'error', LOG_ERR];
        yield [E_WARNING, 'warning', LOG_WARNING];
        yield [E_NOTICE, 'notice', LOG_NOTICE];
        yield [E_STRICT, 'strict', LOG_NOTICE];
        yield [E_STRICT, 'strict', LOG_NOTICE];
        yield [E_USER_DEPRECATED, 'deprecated', LOG_NOTICE];
    }

    /**
     * @dataProvider errorCodeProvider
     */
    public function testMappings(int $phpCode, string $label, int $logLevel): void
    {
        $error = new PhpError($phpCode, 'something bad');
        $this->assertSame($phpCode, $error->getCode());
        $this->assertSame($label, $error->getLabel());
        $this->assertSame($logLevel, $error->getLogLevel());
    }

    public function testGetTraceAsString(): void
    {
        $trace = [
            ['file' => 'a.php', 'line' => 10, 'reference' => 'TestObject::a()'],
            ['file' => 'b.php', 'line' => 5, 'reference' => '[main]'],
        ];
        $error = new PhpError(E_ERROR, 'something bad', __FILE__, __LINE__, $trace);
        $this->assertSame($trace, $error->getTrace());
        $expected = [
            'TestObject::a() a.php, line 10',
            '[main] b.php, line 5',
        ];
        $this->assertSame(implode("\n", $expected), $error->getTraceAsString());
        $this->assertSame('error', $error->getLabel());
    }
}
