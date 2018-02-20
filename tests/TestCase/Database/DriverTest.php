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
 * @since         3.2.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver;
use Cake\Database\Driver\Mysql;
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
        parent::setUp();

        $this->driver = $this->getMockForAbstractClass(Driver::class);
    }

    /**
     * Test if building the object throws an exception if we're not passing
     * required config data.
     *
     * @return void
     */
    public function testConstructorException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please pass "username" instead of "login" for connecting to the database');
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

        $this->assertTrue($driver->isAutoQuotingEnabled());

        $arg = ['username' => 'GummyBear'];
        $driver = $this->getMockForAbstractClass(Driver::class, [$arg]);

        $this->assertFalse($driver->isAutoQuotingEnabled());
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
        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttribute'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('getAttribute')
            ->with(PDO::ATTR_DRIVER_NAME)
            ->willReturn('mysql');

        $this->driver->setConnection($connection);

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
        $result = $this->driver->schemaValue($input);
        $this->assertSame($expected, $result);
    }

    /**
     * Test schemaValue().
     * Asserting that quote() is being called because none of the conditions were met before.
     *
     * @return void
     */
    public function testSchemaValueConnectionQuoting()
    {
        $value = 'string';

        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->setMethods(['quote'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('quote')
            ->with($value, PDO::PARAM_STR);

        $this->driver->setConnection($connection);

        $this->driver->schemaValue($value);
    }

    /**
     * Test lastInsertId().
     *
     * @return void
     */
    public function testLastInsertId()
    {
        $connection = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->setMethods(['lastInsertId'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('all-the-bears');

        $this->driver->setConnection($connection);
        $this->assertSame('all-the-bears', $this->driver->lastInsertId());
    }

    /**
     * Test isConnected().
     *
     * @return void
     */
    public function testIsConnected()
    {
        $this->assertFalse($this->driver->isConnected());

        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->setMethods(['query'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $this->driver->setConnection($connection);
        $this->assertTrue($this->driver->isConnected());
    }

    /**
     * test autoQuoting().
     *
     * @return void
     */
    public function testAutoQuoting()
    {
        $this->assertFalse($this->driver->isAutoQuotingEnabled());

        $this->assertSame($this->driver, $this->driver->enableAutoQuoting(true));
        $this->assertTrue($this->driver->isAutoQuotingEnabled());

        $this->driver->enableAutoQuoting(false);
        $this->assertFalse($this->driver->isAutoQuotingEnabled());

        $this->driver->enableAutoQuoting('string');
        $this->assertTrue($this->driver->isAutoQuotingEnabled());

        $this->driver->enableAutoQuoting('0');
        $this->assertFalse($this->driver->isAutoQuotingEnabled());

        $this->driver->enableAutoQuoting(1);
        $this->assertTrue($this->driver->isAutoQuotingEnabled());

        $this->driver->enableAutoQuoting(0);
        $this->assertFalse($this->driver->isAutoQuotingEnabled());
    }

    /**
     * Test compileQuery().
     *
     * @return void
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
        $this->driver->setConnection(true);
        $this->driver->__destruct();

        $this->assertNull($this->driver->getConnection());
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
            [1, '1'],
            ['0', '0'],
            ['42', '42']
        ];
    }
}
