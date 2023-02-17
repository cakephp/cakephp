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

use Cake\Datasource\Exception\MissingModelException;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RepositoryInterface;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Datasource\StubFactory;
use TestApp\Model\Table\PaginatorPostsTable;
use TestApp\Stub\Stub;
use UnexpectedValueException;

/**
 * ModelAwareTrait test case
 */
class ModelAwareTraitTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        FactoryLocator::drop('Test');
    }

    /**
     * Test set modelClass
     */
    public function testSetModelClass(): void
    {
        $stub = new Stub();
        $this->assertNull($stub->getModelClass());

        $stub->setProps('StubArticles');
        $this->assertSame('StubArticles', $stub->getModelClass());
    }

    public function testFetchModel(): void
    {
        $stub = new Stub();
        $stub->setProps('Articles');
        $stub->setModelType('Table');

        $result = $stub->fetchModel();
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertNull($stub->Articles);

        $result = $stub->fetchModel('Comments');
        $this->assertInstanceOf('Cake\ORM\Table', $result);
        $this->assertNull($stub->Comments);

        $result = $stub->fetchModel(PaginatorPostsTable::class);
        $this->assertInstanceOf(PaginatorPostsTable::class, $result);
        $this->assertSame('PaginatorPosts', $result->getAlias());
        $this->assertNull($stub->PaginatorPosts);
    }

    /**
     * Test that calling fetchModel() without $modelClass argument when default
     * $modelClass property is empty generates exception.
     */
    public function testFetchModelException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Default modelClass is empty');

        $stub = new Stub();
        $stub->setProps('');
        $stub->setModelType('Table');

        $stub->fetchModel();
    }

    /**
     * test MissingModelException being thrown
     */
    public function testFetchModelMissingModelException(): void
    {
        $this->expectException(MissingModelException::class);
        $this->expectExceptionMessage('Model class "Magic" of type "Test" could not be found.');
        $stub = new Stub();

        $locator = new StubFactory();
        FactoryLocator::add('Test', $locator);
        $stub->fetchModel('Magic', 'Test');
    }

    /**
     * test fetchModel() with plugin prefixed models
     */
    public function testFetchModelPlugin(): void
    {
        $stub = new Stub();
        $stub->setProps('Articles');
        $stub->setModelType('Table');

        $result = $stub->fetchModel('TestPlugin.Comments');
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $result);
        $this->assertNull($stub->Comments);
    }

    /**
     * test loadModel()
     */
    public function testLoadModel(): void
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
     */
    public function testLoadModelException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Default modelClass is empty');

        $this->deprecated(function () {
            $stub = new Stub();
            $stub->setProps('');
            $stub->setModelType('Table');

            $stub->loadModel();
        });
    }

    /**
     * test loadModel() with plugin prefixed models
     *
     * Load model should not be called with Foo.Model Bar.Model Model
     * But if it is, the first call wins.
     */
    public function testLoadModelPlugin(): void
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
     */
    public function testModelFactory(): void
    {
        $stub = new Stub();
        $stub->setProps('Articles');

        $mock = $this->getMockBuilder(RepositoryInterface::class)->getMock();
        $mock->expects($this->any())
            ->method('getAlias')
            ->willReturn('Magic');

        $locator = new StubFactory();
        $locator->set('Magic', $mock);
        $stub->modelFactory('Table', $locator);

        $result = $stub->loadModel('Magic', 'Table');
        $this->assertInstanceOf(RepositoryInterface::class, $result);
        $this->assertInstanceOf(RepositoryInterface::class, $stub->Magic);
        $this->assertSame('Magic', $stub->Magic->getAlias());

        $locator = new StubFactory();
        $mock2 = $this->getMockBuilder(RepositoryInterface::class)->getMock();
        $mock2->expects($this->any())
            ->method('getAlias')
            ->willReturn('Foo');
        $locator->set('Foo', $mock2);

        $stub->modelFactory('MyType', $locator);
        $result = $stub->loadModel('Foo', 'MyType');
        $this->assertInstanceOf(RepositoryInterface::class, $result);
        $this->assertSame('Foo', $stub->Foo->getAlias());
    }

    public function testModelFactoryException(): void
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
     */
    public function testGetSetModelType(): void
    {
        $stub = new Stub();
        $stub->setProps('Articles');

        $stub->setModelType('Test');
        $this->assertSame('Test', $stub->getModelType());
    }

    /**
     * test MissingModelException being thrown
     */
    public function testLoadModelMissingModelException(): void
    {
        $this->expectException(MissingModelException::class);
        $this->expectExceptionMessage('Model class "Magic" of type "Test" could not be found.');
        $stub = new Stub();

        $locator = new StubFactory();
        FactoryLocator::add('Test', $locator);

        $stub->loadModel('Magic', 'Test');
    }
}
