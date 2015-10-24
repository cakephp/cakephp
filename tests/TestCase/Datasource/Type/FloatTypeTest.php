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
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * Test for the Float type.
 */
class FloatTypeTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = Type::build('float');
        $this->locale = I18n::locale();

        I18n::locale($this->locale);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        I18n::locale($this->locale);
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
        $this->assertSame(0.0, $result);

        $result = $this->type->toPHP('2');
        $this->assertSame(2.0, $result);

        $result = $this->type->toPHP('2 bears');
        $this->assertSame(2.0, $result);

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
        $this->assertSame(0.0, $result);

        $result = $this->type->toDatasource(2);
        $this->assertSame(2.0, $result);

        $result = $this->type->toDatasource('2.51');
        $this->assertSame(2.51, $result);

        $result = $this->type->toDatasource(['3', '4']);
        $this->assertSame(1, $result);
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

        $result = $this->type->marshal('2.51');
        $this->assertSame(2.51, $result);

        $result = $this->type->marshal('3.5 bears');
        $this->assertSame('3.5 bears', $result);

        $result = $this->type->marshal(['3', '4']);
        $this->assertSame(1, $result);
    }

    /**
     * Tests marshalling numbers using the locale aware parser
     *
     * @return void
     */
    public function testMarshalWithLocaleParsing()
    {
        I18n::locale('de_DE');
        $this->type->useLocaleParser();
        $expected = 1234.53;
        $result = $this->type->marshal('1.234,53');
        $this->assertEquals($expected, $result);

        I18n::locale('en_US');
        $this->type->useLocaleParser();
        $expected = 1234;
        $result = $this->type->marshal('1,234');
        $this->assertEquals($expected, $result);

        I18n::locale('pt_BR');
        $this->type->useLocaleParser();
        $expected = 5987123.231;
        $result = $this->type->marshal('5.987.123,231');
        $this->assertEquals($expected, $result);
    }
}
