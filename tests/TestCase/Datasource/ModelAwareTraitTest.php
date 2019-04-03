<?php
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
namespace Cake\Test\TestCase\Datasource;

use Cake\Datasource\FactoryLocator;
use Cake\Datasource\ModelAwareTrait;
use Cake\TestSuite\TestCase;

/**
 * Testing stub.
 */
class Stub
{

    use ModelAwareTrait;

    public function setProps($name)
    {
        $this->_setModelClass($name);
    }
}

/**
 * ModelAwareTrait test case
 */
class ModelAwareTraitTest extends TestCase
{

    /**
     * Test set modelClass
     *
     * @return void
     */
    public function testSetModelClass()
    {
        $stub = new Stub();
        $this->assertNull($stub->modelClass);

        $stub->setProps('StubArticles');
        $this->assertEquals('StubArticles', $stub->modelClass);
    }

    /**
     * test loadModel()
     *
     * @return void
     */
    public function testLoadModel()
    {
        $stub = new Stub();
        $stub->setProps('Articles');
        $stub->modelType('Table');

        $result = $stub->loadModel();
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertInstanceOf('Cake\ORM\Table', $stub->Articles);

        $result = $stub->loadModel('Comments');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertInstanceOf('Cake\ORM\Table', $stub->Comments);
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
     * test getModelType() and setModelType()
     *
     * @return void
     */
    public function testGetSetModelType()
    {
        $stub = new Stub();
        $stub->setProps('Articles');

        FactoryLocator::add('Test', function ($name) {
            $mock = new \StdClass();
            $mock->name = $name;

            return $mock;
        });
        $stub->setModelType('Test');
        $this->assertSame('Test', $stub->getModelType());
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
