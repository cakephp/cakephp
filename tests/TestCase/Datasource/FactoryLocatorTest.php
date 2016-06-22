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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\TestCase;

/**
 * FactoryLocatorTest test case
 */
class FactoryLocatorTest extends TestCase
{
    /**
     * Test get factory
     *
     * @return void
     */
    public function testGet()
    {
        $this->assertInternalType('callable', FactoryLocator::get('Table'));
    }

    /**
     * Test get non existing factory
     *
     * @return void
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown repository type "Test". Make sure you register a type before trying to use it.
     */
    public function testGetNonExisting()
    {
        FactoryLocator::get('Test');
    }

    /**
     * test add()
     *
     * @return void
     */
    public function testAdd()
    {
        FactoryLocator::add('Test', function ($name) {
            $mock = new \StdClass();
            $mock->name = $name;
            return $mock;
        });

        $this->assertInternalType('callable', FactoryLocator::get('Test'));
    }

    /**
     * test drop()
     *
     * @return void
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown repository type "Test". Make sure you register a type before trying to use it.
     */
    public function testDrop()
    {
        FactoryLocator::drop('Test');

        FactoryLocator::get('Test');
    }

    /**
     * test loadModel() with plugin prefixed models
     *
     * Load model should not be called with Foo.Model Bar.Model Model
     * But if it is, the first call wins.
     *
     * @return void
     */
    public function testLoadModelPlugin()
    {
        $stub = new Stub();
        $stub->setProps('Articles');
        $stub->modelType('Table');

        $result = $stub->loadModel('TestPlugin.Comments');
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $result);
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $stub->Comments);

        $result = $stub->loadModel('Comments');
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $result);
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $stub->Comments);
    }

    /**
     * test alternate model factories.
     *
     * @return void
     */
    public function testModelFactory()
    {
        $stub = new Stub();
        $stub->setProps('Articles');

        $stub->modelFactory('Table', function ($name) {
            $mock = new \StdClass();
            $mock->name = $name;
            return $mock;
        });

        $result = $stub->loadModel('Magic', 'Table');
        $this->assertInstanceOf('\StdClass', $result);
        $this->assertInstanceOf('\StdClass', $stub->Magic);
        $this->assertEquals('Magic', $stub->Magic->name);
    }

    /**
     * test alternate default model type.
     *
     * @return void
     */
    public function testModelType()
    {
        $stub = new Stub();
        $stub->setProps('Articles');

        FactoryLocator::add('Test', function ($name) {
            $mock = new \StdClass();
            $mock->name = $name;
            return $mock;
        });
        $stub->modelType('Test');

        $result = $stub->loadModel('Magic');
        $this->assertInstanceOf('\StdClass', $result);
        $this->assertInstanceOf('\StdClass', $stub->Magic);
        $this->assertEquals('Magic', $stub->Magic->name);
    }

    /**
     * test MissingModelException being thrown
     *
     * @return void
     * @expectedException \Cake\Datasource\Exception\MissingModelException
     * @expectedExceptionMessage Model class "Magic" of type "Test" could not be found.
     */
    public function testMissingModelException()
    {
        $stub = new Stub();

        FactoryLocator::add('Test', function ($name) {
            return false;
        });

        $stub->loadModel('Magic', 'Test');
    }

    public function tearDown()
    {
        FactoryLocator::drop('Test');

        parent::tearDown();
    }
}
