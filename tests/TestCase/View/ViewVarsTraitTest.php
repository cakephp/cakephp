<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\TestSuite\TestCase;
use Cake\View\ViewVarsTrait;

/**
 * ViewVarsTrait test case
 *
 */
class ViewVarsTraitTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->subject = $this->getObjectForTrait('Cake\View\ViewVarsTrait');
    }

    /**
     * Test set() with one param.
     *
     * @return void
     */
    public function testSetOneParam()
    {
        $data = ['test' => 'val', 'foo' => 'bar'];
        $this->subject->set($data);
        $this->assertEquals($data, $this->subject->viewVars);

        $update = ['test' => 'updated'];
        $this->subject->set($update);
        $this->assertEquals('updated', $this->subject->viewVars['test']);
    }

    /**
     * test set() with 2 params
     *
     * @return void
     */
    public function testSetTwoParam()
    {
        $this->subject->set('testing', 'value');
        $this->assertEquals(['testing' => 'value'], $this->subject->viewVars);
    }

    /**
     * test chainable set()
     *
     * @return void
     */
    public function testSetChained()
    {
        $result = $this->subject->set('testing', 'value')
            ->set('foo', 'bar');
        $this->assertSame($this->subject, $result);
        $this->assertEquals(['testing' => 'value', 'foo' => 'bar'], $this->subject->viewVars);
    }

    /**
     * test set() with 2 params in combine mode
     *
     * @return void
     */
    public function testSetTwoParamCombind()
    {
        $keys = ['one', 'key'];
        $vals = ['two', 'val'];
        $this->subject->set($keys, $vals);

        $expected = ['one' => 'two', 'key' => 'val'];
        $this->assertEquals($expected, $this->subject->viewVars);
    }

    /**
     * test viewOptions() with 1 string param, merge true
     *
     * @return void
     */
    public function testAddOneViewOption()
    {
        $option = 'newOption';
        $this->subject->viewOptions($option);

        $this->assertContains($option, $this->subject->_validViewOptions);
    }

    /**
     * test viewOptions() with 2 strings in array, merge true.
     *
     * @return void
     */
    public function testAddTwoViewOption()
    {
        $this->subject->_validViewOptions = ['oldOption'];
        $option = ['newOption', 'anotherOption'];
        $result = $this->subject->viewOptions($option);
        $expects = ['oldOption', 'newOption', 'anotherOption'];

        $this->assertContainsOnly('string', $result);
        $this->assertEquals($expects, $result);
    }

    /**
     * test empty params reads _validViewOptions.
     *
     * @return void
     */
    public function testReadingViewOptions()
    {
        $expected = $this->subject->_validViewOptions = ['one', 'two', 'three'];
        $result = $this->subject->viewOptions();

        $this->assertEquals($expected, $result);
    }

    /**
     * test setting $merge `false` overrides currect options.
     *
     * @return void
     */
    public function testMergeFalseViewOptions()
    {
        $this->subject->_validViewOptions = ['one', 'two', 'three'];
        $expected = ['four', 'five', 'six'];
        $result = $this->subject->viewOptions($expected, false);

        $this->assertEquals($expected, $result);
    }

    /**
     * test _validViewOptions is undefined and $opts is null, an empty array is returned.
     *
     * @return void
     */
    public function testUndefinedValidViewOptions()
    {
        $result = $this->subject->viewOptions();

        $this->assertTrue(is_array($result));
        $this->assertTrue(empty($result));
    }

    /**
     * test getView() throws exception if view class cannot be found
     *
     * @expectedException \Cake\View\Exception\MissingViewException
     * @expectedExceptionMessage View class "Foo" is missing.
     * @return void
     */
    public function testGetViewException()
    {
        $this->subject->getView('Foo');
    }

    /**
     * test createView() throws exception if view class cannot be found
     *
     * @expectedException \Cake\View\Exception\MissingViewException
     * @expectedExceptionMessage View class "Foo" is missing.
     * @return void
     */
    public function testCreateViewException()
    {
        $this->subject->getView('Foo');
    }
}
