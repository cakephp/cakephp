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
namespace Cake\Test\TestCase\Datasource;

use Cake\Datasource\FactoryLocator;
use Cake\Datasource\Locator\LocatorInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Model\Table\PaginatorPostsTable;
use TestApp\Stub\Stub;

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
        $this->assertNull($stub->getModelClass());

        $stub->setProps('StubArticles');
        $this->assertSame('StubArticles', $stub->getModelClass());
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
        $stub->setModelType('Table');

        $result = $stub->loadModel();
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertInstanceOf('Cake\ORM\Table', $stub->Articles);

        $result = $stub->loadModel('Comments');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertInstanceOf('Cake\ORM\Table', $stub->Comments);

        $result = $stub->loadModel(PaginatorPostsTable::class);
        $this->assertInstanceOf(PaginatorPostsTable::class, $result);
        $this->assertInstanceOf(PaginatorPostsTable::class, $stub->PaginatorPosts);
        $this->assertSame('PaginatorPosts', $result->getAlias());
    }

    /**
     * Test that calling loadModel() without $modelClass argument when default
     * $modelClass property is empty generates exception.
     *
     * @return void
     */
    public function testLoadModelException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Default modelClass is empty');

        $stub = new Stub();
        $stub->setProps('');
        $stub->setModelType('Table');

        $stub->loadModel();
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
        $stub->setModelType('Table');

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
            $mock = $this->getMockBuilder(RepositoryInterface::class)->getMock();
            $mock->name = $name;

            return $mock;
        });

        $result = $stub->loadModel('Magic', 'Table');
        $this->assertInstanceOf(RepositoryInterface::class, $result);
        $this->assertInstanceOf(RepositoryInterface::class, $stub->Magic);
        $this->assertSame('Magic', $stub->Magic->name);

        $locator = $this->getMockBuilder(LocatorInterface::class)->getMock();
        $mock2 = $this->getMockBuilder(RepositoryInterface::class)->getMock();
        $mock2->alias = 'Foo';
        $locator->expects($this->any())
            ->method('get')
            ->willReturn($mock2);

        $stub->modelFactory('MyType', $locator);
        $result = $stub->loadModel('Foo', 'MyType');
        $this->assertInstanceOf(RepositoryInterface::class, $result);
        $this->assertSame('Foo', $stub->Foo->alias);
    }

    public function testModelFactoryException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '`$factory` must be an instance of Cake\Datasource\Locator\LocatorInterface or a callable.'
            . ' Got type `string` instead.'
        );

        $stub = new Stub();
        $stub->modelFactory('MyType', 'fail');
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
            $mock = new \stdClass();
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
     */
    public function testMissingModelException()
    {
        $this->expectException(\Cake\Datasource\Exception\MissingModelException::class);
        $this->expectExceptionMessage('Model class "Magic" of type "Test" could not be found.');
        $stub = new Stub();

        FactoryLocator::add('Test', function ($name) {
            return false;
        });

        $stub->loadModel('Magic', 'Test');
    }

    public function tearDown(): void
    {
        FactoryLocator::drop('Test');

        parent::tearDown();
    }
}
