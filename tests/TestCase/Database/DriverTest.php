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
 * @since         3.2.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Driver;
use Cake\Database\Driver\Mysql;
use Cake\Database\DriverInterface;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Query;
use Cake\Database\QueryCompiler;
use Cake\Database\Schema\TableSchema;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use Exception;
use PDO;
use PDOStatement;

/**
 * Tests Driver class
 */
class DriverTest extends TestCase
{
    /**
     * @var \Cake\Database\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->driver = $this->getMockForAbstractClass(Driver::class);
    }

    /**
     * Test if building the object throws an exception if we're not passing
     * required config data.
     */
    public function testConstructorException(): void
    {
        $arg = ['login' => 'Bear'];
        try {
            $this->getMockForAbstractClass(Driver::class, [$arg]);
        } catch (Exception $e) {
            $this->assertStringContainsString(
                'Please pass "username" instead of "login" for connecting to the database',
                $e->getMessage()
            );
        }
    }

    /**
     * Test the constructor.
     */
    public function testConstructor(): void
    {
        $arg = ['quoteIdentifiers' => true];
        $driver = $this->getMockForAbstractClass(Driver::class, [$arg]);

        $this->assertTrue($driver->isAutoQuotingEnabled());

        $arg = ['username' => 'GummyBear'];
        $driver = $this->getMockForAbstractClass(Driver::class, [$arg]);

        $this->assertFalse($driver->isAutoQuotingEnabled());
    }

    /**
     * Tests default implementation of feature support check.
     */
    public function testSupports(): void
    {
        $this->assertTrue($this->driver->supports(DriverInterface::FEATURE_SAVEPOINT));
        $this->assertTrue($this->driver->supports(DriverInterface::FEATURE_QUOTE));

        $this->assertFalse($this->driver->supports(DriverInterface::FEATURE_CTE));
        $this->assertFalse($this->driver->supports(DriverInterface::FEATURE_JSON));
        $this->assertFalse($this->driver->supports(DriverInterface::FEATURE_WINDOW));

        $this->assertFalse($this->driver->supports('this-is-fake'));
    }

    /**
     * Tests deprecated supports checks.
     */
    public function testDeprecatedSupports(): void
    {
        $this->deprecated(function () {
            $this->assertSame($this->driver->supportsCTEs(), $this->driver->supports(DriverInterface::FEATURE_CTE));
            $this->assertSame($this->driver->supportsSavePoints(), $this->driver->supports(DriverInterface::FEATURE_SAVEPOINT));
            $this->assertSame($this->driver->supportsQuoting(), $this->driver->supports(DriverInterface::FEATURE_QUOTE));
        });
    }

    /**
     * Test schemaValue().
     * Uses a provider for all the different values we can pass to the method.
     *
     * @dataProvider schemaValueProvider
     * @param mixed $input
     */
    public function testSchemaValue($input, string $expected): void
    {
        $result = $this->driver->schemaValue($input);
        $this->assertSame($expected, $result);
    }

    /**
     * Test schemaValue().
     * Asserting that quote() is being called because none of the conditions were met before.
     */
    public function testSchemaValueConnectionQuoting(): void
    {
        $value = 'string';

        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['quote'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('quote')
            ->with($value, PDO::PARAM_STR)
            ->will($this->returnValue('string'));

        $this->driver->setConnection($connection);
        $this->driver->schemaValue($value);
    }

    /**
     * Test lastInsertId().
     */
    public function testLastInsertId(): void
    {
        $connection = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['lastInsertId'])
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
     */
    public function testIsConnected(): void
    {
        $this->assertFalse($this->driver->isConnected());

        $connection = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['query'])
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('query')
            ->willReturn(new PDOStatement());

        $this->driver->setConnection($connection);
        $this->assertTrue($this->driver->isConnected());
    }

    /**
     * test autoQuoting().
     */
    public function testAutoQuoting(): void
    {
        $this->assertFalse($this->driver->isAutoQuotingEnabled());

        $this->assertSame($this->driver, $this->driver->enableAutoQuoting(true));
        $this->assertTrue($this->driver->isAutoQuotingEnabled());

        $this->driver->disableAutoQuoting();
        $this->assertFalse($this->driver->isAutoQuotingEnabled());
    }

    /**
     * Test compileQuery().
     */
    public function testCompileQuery(): void
    {
        $compiler = $this->getMockBuilder(QueryCompiler::class)
            ->onlyMethods(['compile'])
            ->getMock();

        $compiler
            ->expects($this->once())
            ->method('compile')
            ->willReturn('1');

        $driver = $this->getMockBuilder(Driver::class)
            ->onlyMethods(['newCompiler', 'queryTranslator'])
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
        $query->method('type')->will($this->returnValue('select'));

        $result = $driver->compileQuery($query, new ValueBinder());

        $this->assertIsArray($result);
        $this->assertSame($query, $result[0]);
        $this->assertSame('1', $result[1]);
    }

    /**
     * Test newCompiler().
     */
    public function testNewCompiler(): void
    {
        $this->assertInstanceOf(QueryCompiler::class, $this->driver->newCompiler());
    }

    /**
     * Test newTableSchema().
     */
    public function testNewTableSchema(): void
    {
        $tableName = 'articles';
        $actual = $this->driver->newTableSchema($tableName);
        $this->assertInstanceOf(TableSchema::class, $actual);
        $this->assertSame($tableName, $actual->name());
    }

    /**
     * Test __destruct().
     */
    public function testDestructor(): void
    {
        $this->driver->setConnection(true);
        $this->driver->__destruct();

        $this->expectException(MissingConnectionException::class);
        $this->driver->getConnection();
    }

    /**
     * Data provider for testSchemaValue().
     *
     * @return array
     */
    public function schemaValueProvider(): array
    {
        return [
            [null, 'NULL'],
            [false, 'FALSE'],
            [true, 'TRUE'],
            [1, '1'],
            ['0', '0'],
            ['42', '42'],
        ];
    }
}
