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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Datasource\Type;
use Cake\TestSuite\TestCase;
use \PDO;

/**
 * Test for the Binary type.
 */
class SqlserverBinaryTypeTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Type::map('binary', 'Cake\Database\Type\SqlserverBinaryType');

        $this->type = Type::build('binary');
        $this->driver = $this->getMock('Cake\Database\Driver');
    }

    /**
     * SQLServer returns binary fields as hexidecimal
     * Ensure decoding happens for SQLServer drivers
     *
     * @return void
     */
    public function testToPHPSqlserver()
    {
        $result = $this->type->toPHP('536F6D652076616C7565');
        $this->assertInternalType('resource', $result);
        $this->assertSame('Some value', stream_get_contents($result));
    }

    /**
     * Test exceptions on invalid data.
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @expectedExceptionMessage Unable to convert array into binary.
     */
    public function testToPHPFailure()
    {
        $this->type->toPHP([]);
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatasource()
    {
        $value = 'some data';
        $result = $this->type->toDatasource($value);
        $this->assertEquals($value, $result);

        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toDatasource($fh);
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
