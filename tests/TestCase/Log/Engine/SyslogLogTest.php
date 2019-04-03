<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log\Engine;

use Cake\TestSuite\TestCase;

/**
 * SyslogLogTest class
 */
class SyslogLogTest extends TestCase
{

    /**
     * Tests that the connection to the logger is open with the right arguments
     *
     * @return void
     */
    public function testOpenLog()
    {
        $log = $this->getMockBuilder('Cake\Log\Engine\SyslogLog')
            ->setMethods(['_open', '_write'])
            ->getMock();
        $log->expects($this->once())->method('_open')->with('', LOG_ODELAY, LOG_USER);
        $log->log('debug', 'message');

        $log = $this->getMockBuilder('Cake\Log\Engine\SyslogLog')
            ->setMethods(['_open', '_write'])
            ->getMock();
        $log->config([
            'prefix' => 'thing',
            'flag' => LOG_NDELAY,
            'facility' => LOG_MAIL,
            'format' => '%s: %s'
        ]);
        $log->expects($this->once())->method('_open')
            ->with('thing', LOG_NDELAY, LOG_MAIL);
        $log->log('debug', 'message');
    }

    /**
     * Tests that single lines are written to syslog
     *
     * @dataProvider typesProvider
     * @return void
     */
    public function testWriteOneLine($type, $expected)
    {
        $log = $this->getMockBuilder('Cake\Log\Engine\SyslogLog')
            ->setMethods(['_open', '_write'])
            ->getMock();
        $log->expects($this->once())->method('_write')->with($expected, $type . ': Foo');
        $log->log($type, 'Foo');
    }

    /**
     * Tests that multiple lines are split and logged separately
     *
     * @return void
     */
    public function testWriteMultiLine()
    {
        $log = $this->getMockBuilder('Cake\Log\Engine\SyslogLog')
            ->setMethods(['_open', '_write'])
            ->getMock();
        $log->expects($this->at(1))->method('_write')->with(LOG_DEBUG, 'debug: Foo');
        $log->expects($this->at(2))->method('_write')->with(LOG_DEBUG, 'debug: Bar');
        $log->expects($this->exactly(2))->method('_write');
        $log->log('debug', "Foo\nBar");
    }

    /**
     * Data provider for the write function test
     *
     * @return array
     */
    public function typesProvider()
    {
        return [
            ['emergency', LOG_EMERG],
            ['alert', LOG_ALERT],
            ['critical', LOG_CRIT],
            ['error', LOG_ERR],
            ['warning', LOG_WARNING],
            ['notice', LOG_NOTICE],
            ['info', LOG_INFO],
            ['debug', LOG_DEBUG]
        ];
    }
}
