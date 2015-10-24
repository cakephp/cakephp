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

use Cake\Database\Type;
use Cake\TestSuite\TestCase;
use \PDO;

/**
 * Test for the Integer type.
 */
class IntegerTypeTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = Type::build('integer');
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
        $this->assertSame(0, $result);

        $result = $this->type->toPHP('2');
        $this->assertSame(2, $result);

        $result = $this->type->toPHP('2 bears');
        $this->assertSame(2, $result);

        $result = $this->type->toPHP(['3', '4']);
        $this->assertSame(1, $result);
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatasource()
    {
        $result = $this->type->toDatasource('some data');
        $this->assertSame(0, $result);

        $result = $this->type->toDatasource(2);
        $this->assertSame(2, $result);

        $result = $this->type->toDatasource('2');
        $this->assertSame(2, $result);
    }

    /**
     * Tests that passing an invalid value will throw an exception
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testToDatasourceInvalid()
    {
        $this->type->toDatasource(['3', '4']);
    }

    /**
     * Test marshalling
     *
     * @return void
     */
    public function testMarshal()
    {
        $result = $this->type->marshal('some data');
        $this->assertSame('some data', $result);

        $result = $this->type->marshal('');
        $this->assertNull($result);

        $result = $this->type->marshal('0');
        $this->assertSame(0, $result);

        $result = $this->type->marshal('105');
        $this->assertSame(105, $result);

        $result = $this->type->marshal(105);
        $this->assertSame(105, $result);

        $result = $this->type->marshal('1.25');
        $this->assertSame('1.25', $result);

        $result = $this->type->marshal('2 monkeys');
        $this->assertSame('2 monkeys', $result);

        $result = $this->type->marshal(['3', '4']);
        $this->assertSame(1, $result);
    }
}
