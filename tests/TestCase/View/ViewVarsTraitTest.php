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
use Cake\View\Exception\MissingViewException;

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
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new Controller();
    }

    /**
     * Test set() with one param.
     */
    public function testSetOneParam(): void
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
     */
    public function testSetTwoParam(): void
    {
        $this->subject->set('testing', 'value');
        $this->assertEquals(['testing' => 'value'], $this->subject->viewBuilder()->getVars());
    }

    /**
     * test chainable set()
     */
    public function testSetChained(): void
    {
        $result = $this->subject->set('testing', 'value')
            ->set('foo', 'bar');
        $this->assertSame($this->subject, $result);
        $this->assertEquals(['testing' => 'value', 'foo' => 'bar'], $this->subject->viewBuilder()->getVars());
    }

    /**
     * test set() with 2 params in combine mode
     */
    public function testSetTwoParamCombined(): void
    {
        $keys = ['one', 'key'];
        $vals = ['two', 'val'];
        $this->subject->set($keys, $vals);

        $expected = ['one' => 'two', 'key' => 'val'];
        $this->assertEquals($expected, $this->subject->viewBuilder()->getVars());
    }

    /**
     * test that createView() updates viewVars of View instance on each call.
     */
    public function testUptoDateViewVars(): void
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
     */
    public function testCreateViewParameter(): void
    {
        $this->subject->viewBuilder()->setClassName('View');
        $view = $this->subject->createView('Xml');
        $this->assertInstanceOf('Cake\View\XmlView', $view);
    }

    /**
     * test createView() throws exception if view class cannot be found
     */
    public function testCreateViewException(): void
    {
        $this->expectException(MissingViewException::class);
        $this->expectExceptionMessage('View class "Foo" is missing.');
        $this->subject->createView('Foo');
    }
}
