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

/**
 * Test for the Uuid type.
 */
class UuidTypeTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = Type::build('uuid');
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
        $this->assertSame('some data', $result);

        $result = $this->type->toPHP(2);
        $this->assertSame('2', $result);
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatasource()
    {
        $result = $this->type->toDatasource('some data');
        $this->assertSame('some data', $result);

        $result = $this->type->toDatasource(2);
        $this->assertSame('2', $result);
    }

    /**
     * Test generating new ids
     *
     * @return void
     */
    public function testNewId()
    {
        $one = $this->type->newId();
        $two = $this->type->newId();

        $this->assertNotEquals($one, $two, 'Should be different values');
        $this->assertRegExp('/^[a-f0-9-]+$/', $one, 'Should quack like a uuid');
        $this->assertRegExp('/^[a-f0-9-]+$/', $two, 'Should quack like a uuid');
    }

    /**
     * Tests that marshalling an empty string results in null
     *
     * @return void
     */
    public function testMarshalEmptyString()
    {
        $this->assertNull($this->type->marshal(''));
    }
}
