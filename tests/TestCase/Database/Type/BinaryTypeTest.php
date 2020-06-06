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
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test for the Binary type.
 */
class BinaryTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\BinaryType
     */
    public $type;

    /**
     * @var \Cake\Database\Driver
     */
    public $driver;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = Type::build('binary');
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
    }

    /**
     * Test toPHP
     *
     * @return void
     */
    public function testToPHP()
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));

        $result = $this->type->toPHP('some data', $this->driver);
        $this->assertInternalType('resource', $result);

        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toPHP($fh, $this->driver);
        $this->assertSame($fh, $result);
        fclose($fh);
    }

    /**
     * SQLServer returns binary fields as hexadecimal
     * Ensure decoding happens for SQLServer drivers
     *
     * @return void
     */
    public function testToPHPSqlserver()
    {
        if (version_compare(PHP_VERSION, '7.0', '>')) {
            $this->markTestSkipped('PHP 7 MSSQL Drivers does not return binary fields as hexadecimal.');
        }

        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlserver')
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->type->toPHP('536F6D652076616C7565', $driver);
        $this->assertInternalType('resource', $result);
        $this->assertSame('Some value', stream_get_contents($result));
    }

    /**
     * Test exceptions on invalid data.
     */
    public function testToPHPFailure()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->expectExceptionMessage('Unable to convert array into binary.');
        $this->type->toPHP([], $this->driver);
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $value = 'some data';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertEquals($value, $result);

        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toDatabase($fh, $this->driver);
        $this->assertSame($fh, $result);
    }

    /**
     * Test that the PDO binding type is correct.
     *
     * @return void
     */
    public function testToStatement()
    {
        $this->assertEquals(PDO::PARAM_LOB, $this->type->toStatement('', $this->driver));
    }
}
