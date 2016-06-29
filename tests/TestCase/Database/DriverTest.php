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
 * @since         3.2.12
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver;
use Cake\Database\Query;
use Cake\Database\QueryCompiler;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Tests Driver class
 */
class DriverTest extends TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        $this->driver = $this->getMockForAbstractClass(Driver::class);
    }

    /**
     * Test if building the object throws an exception if we're not passing
     * required config data.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Please pass "username" instead of "login" for connecting to the database
     * @return void
     */
    public function testConstructorException()
    {
        $arg = ['login' => 'Bear'];
        $this->getMockForAbstractClass(Driver::class, [$arg]);
    }

    /**
     * Test the constructor.
     *
     * @return void
     */
    public function testConstructor()
    {
        $arg = ['quoteIdentifiers' => true];
        $driver = $this->getMockForAbstractClass(Driver::class, [$arg]);

        $this->assertTrue($driver->autoQuoting());

        $arg = ['username' => 'GummyBear'];
        $driver = $this->getMockForAbstractClass(Driver::class, [$arg]);

        $this->assertFalse($driver->autoQuoting());
    }

    /**
     * Test supportsSavePoints().
     *
     * @return void
     */
    public function testSupportsSavePoints()
    {
        $result = $this->driver->supportsSavePoints();
        $this->assertTrue($result);
    }

    /**
     * Test supportsQuoting().
     *
     * @return void
     */
    public function testSupportsQuoting()
    {
        $result = $this->driver->supportsQuoting();
        $this->assertTrue($result);
    }

    /**
     * Test schemaValue().
     * Uses a provider for all the different values we can pass to the method.
     *
     * @dataProvider schemaValueProvider
     * @return void
     */
    public function testSchemaValue($input, $expected)
    {
        $this->driver->_connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->setMethods(['quote'])
            ->getMock();

        $this->driver->_connection
            ->expects($this->any())
            ->method('quote')
            ->willReturn($expected);

        $result = $this->driver->schemaValue($input);
        $this->assertSame($expected, $result);
    }

    /**
     * Test lastInsertId().
     *
     * @return void
     */
    public function testLastInsertId()
    {
        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->setMethods(['lastInsertId'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('all-the-bears');

        $this->driver->_connection = $connection;
        $this->assertSame('all-the-bears', $this->driver->lastInsertId());
    }

    /**
     * Test isConnected().
     *
     * @return void
     */
    public function testIsConnected()
    {
        $this->driver->_connection = 'connection';
        $this->assertTrue($this->driver->isConnected());

        $this->driver->_connection = null;
        $this->assertFalse($this->driver->isConnected());
    }

    /**
     * test autoQuoting().
     *
     * @return void
     */
    public function testAutoQuoting()
    {
        $this->assertFalse($this->driver->autoQuoting());

        $this->driver->autoQuoting(true);
        $this->assertTrue($this->driver->autoQuoting());

        $this->assertTrue($this->driver->autoQuoting(true));
        $this->assertFalse($this->driver->autoQuoting(false));

        $this->assertTrue($this->driver->autoQuoting('string'));
        $this->assertFalse($this->driver->autoQuoting('0'));

        $this->assertTrue($this->driver->autoQuoting(1));
        $this->assertFalse($this->driver->autoQuoting(0));
    }

    /**
     * Test compileQuery().
     *
     * @return void
     * @group new
     */
    public function testCompileQuery()
    {
        $compiler = $this->getMockBuilder(QueryCompiler::class)
            ->setMethods(['compile'])
            ->getMock();

        $compiler
            ->expects($this->once())
            ->method('compile')
            ->willReturn(true);

        $driver = $this->getMockBuilder(Driver::class)
            ->setMethods(['newCompiler', 'queryTranslator'])
            ->getMockForAbstractClass();

        $driver
            ->expects($this->once())
            ->method('newCompiler')
            ->willReturn($compiler);

        $driver
            ->expects($this->once())
            ->method('queryTranslator')
            ->willReturn(function ($query) {
                return $query;
            });

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $driver->compileQuery($query, new ValueBinder);

        $this->assertInternalType('array', $result);
        $this->assertSame($query, $result[0]);
        $this->assertTrue($result[1]);
    }

    /**
     * Test newCompiler().
     *
     * @return void
     */
    public function testNewCompiler()
    {
        $this->assertInstanceOf(QueryCompiler::class, $this->driver->newCompiler());
    }

    /**
     * Test __destruct().
     *
     * @return void
     */
    public function testDestructor()
    {
        $this->driver->_connection = true;
        $this->driver->__destruct();

        $this->assertNull($this->driver->_connection);
    }

    /**
     * Data provider for testSchemaValue().
     *
     * @return array
     */
    public function schemaValueProvider()
    {
        return [
            [null, 'NULL'],
            [false, 'FALSE'],
            [true, 'TRUE'],
            [1, 1],
            ['0', '0'],
            ['42', '42'],
            ['string', true]
        ];
    }
}
