<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
     * @var \Cake\Controller\Controller
     */
    protected $subject;

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new Controller();
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
        $this->assertEquals($data, $this->subject->viewBuilder()->getVars());

        $update = ['test' => 'updated'];
        $this->subject->set($update);
        $this->assertSame('updated', $this->subject->viewBuilder()->getVar('test'));
    }

    /**
     * test set() with 2 params
     *
     * @return void
     */
    public function testSetTwoParam()
    {
        $this->subject->set('testing', 'value');
        $this->assertEquals(['testing' => 'value'], $this->subject->viewBuilder()->getVars());
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
        $this->assertEquals(['testing' => 'value', 'foo' => 'bar'], $this->subject->viewBuilder()->getVars());
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
        $this->assertEquals($expected, $this->subject->viewBuilder()->getVars());
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
        $this->assertSame('one', $this->subject->createView()->get('one'));

        $expected = ['one' => 'one', 'two' => 'two'];
        $this->subject->set($expected);
        $this->assertSame('two', $this->subject->createView()->get('two'));
    }

    /**
     * test that parameters beats viewBuilder() and viewClass
     *
     * @return void
     */
    public function testCreateViewParameter()
    {
        $this->subject->viewBuilder()->setClassName('View');
        $view = $this->subject->createView('Xml');
        $this->assertInstanceOf('Cake\View\XmlView', $view);
    }

    /**
     * test createView() throws exception if view class cannot be found
     *
     * @return void
     */
    public function testCreateViewException()
    {
        $this->expectException(\Cake\View\Exception\MissingViewException::class);
        $this->expectExceptionMessage('View class "Foo" is missing.');
        $this->subject->createView('Foo');
    }
}
