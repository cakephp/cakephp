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
namespace Cake\Test\TestCase\Datasource\Type;

use Cake\Datasource\Type;
use Cake\TestSuite\TestCase;
use \PDO;

/**
 * Test for the Binary type.
 */
class BinaryTypeTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->type = Type::build('binary');
    }

    /**
     * Test toPHP
     *
     * @return void
     */
    public function testToPHP()
    {
        $this->assertNull($this->type->toPHP(null));

        $result = $this->type->toPHP('some data');
        $this->assertInternalType('resource', $result);

        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toPHP($fh);
        $this->assertSame($fh, $result);
        fclose($fh);
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
}
