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

use Cake\Controller\Controller;
use Cake\TestSuite\TestCase;

/**
 * ViewVarsTrait test case
 */
class ViewVarsTraitTest extends TestCase
{

    /**
     * @var \Cake\Controller\Controller;
     */
    public $subject;

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->subject = new Controller;
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
    public function testSetTwoParamCombined()
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

        $this->assertContains($option, $this->subject->viewOptions());
    }

    /**
     * test viewOptions() with 2 strings in array, merge true.
     *
     * @return void
     */
    public function testAddTwoViewOption()
    {
        $this->subject->viewOptions(['oldOption'], false);
        $option = ['newOption', 'anotherOption'];
        $result = $this->subject->viewOptions($option);
        $expects = ['oldOption', 'newOption', 'anotherOption'];

        $this->assertContainsOnly('string', $result);
        $this->assertEquals($expects, $result);
    }

    /**
     * test empty params reads _viewOptions.
     *
     * @return void
     */
    public function testReadingViewOptions()
    {
        $expected = $this->subject->viewOptions(['one', 'two', 'three'], false);
        $result = $this->subject->viewOptions();

        $this->assertEquals($expected, $result);
    }

    /**
     * test setting $merge `false` overrides correct options.
     *
     * @return void
     */
    public function testMergeFalseViewOptions()
    {
        $this->subject->viewOptions(['one', 'two', 'three'], false);
        $expected = ['four', 'five', 'six'];
        $result = $this->subject->viewOptions($expected, false);

        $this->assertEquals($expected, $result);
    }

    /**
     * test _viewOptions is undefined and $opts is null, an empty array is returned.
     *
     * @return void
     */
    public function testUndefinedValidViewOptions()
    {
        $result = $this->subject->viewOptions([], false);

        $this->assertTrue(is_array($result));
        $this->assertEmpty($result);
    }

    /**
     * test that createView() updates viewVars of View instance on each call.
     *
     * @return void
     */
    public function testUptoDateViewVars()
    {
        $expected = ['one' => 'one'];
        $this->subject->set($expected);
        $this->assertEquals($expected, $this->subject->createView()->viewVars);

        $expected = ['one' => 'one', 'two' => 'two'];
        $this->subject->set($expected);
        $this->assertEquals($expected, $this->subject->createView()->viewVars);
    }

    /**
     * test that options are passed to the view builder when using createView().
     *
     * @return void
     */
    public function testViewOptionsGetsToBuilder()
    {
        $this->subject->passedArgs = 'test';
        $this->subject->createView();
        $result = $this->subject->viewBuilder()->getOptions();
        $this->assertEquals(['passedArgs' => 'test'], $result);
    }

    /**
     * test that viewClass is used to create the view
     *
     * @return void
     */
    public function testCreateViewViewClass()
    {
        $this->subject->viewClass = 'Json';
        $view = $this->subject->createView();
        $this->assertInstanceOf('Cake\View\JsonView', $view);
    }

    /**
     * test that viewBuilder settings override viewClass
     *
     * @return void
     */
    public function testCreateViewViewBuilder()
    {
        $this->subject->viewBuilder()->setClassName('Xml');
        $this->subject->viewClass = 'Json';
        $view = $this->subject->createView();
        $this->assertInstanceOf('Cake\View\XmlView', $view);
    }

    /**
     * test that parameters beats viewBuilder() and viewClass
     *
     * @return void
     */
    public function testCreateViewParameter()
    {
        $this->subject->viewBuilder()->setClassName('View');
        $this->subject->viewClass = 'Json';
        $view = $this->subject->createView('Xml');
        $this->assertInstanceOf('Cake\View\XmlView', $view);
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
        $this->subject->createView('Foo');
    }
}
