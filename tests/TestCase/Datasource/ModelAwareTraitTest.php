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
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use TestApp\Datasource\StubFactory;
use TestApp\Model\Table\PaginatorPostsTable;
use TestApp\Stub\Stub;
use TestPlugin\Model\Table\CommentsTable;
use UnexpectedValueException;

/**
 * ModelAwareTrait test case
 */
class ModelAwareTraitTest extends TestCase
{
    protected function tearDown(): void
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
        $this->assertInstanceOf(Table::class, $result);
        $this->assertNull($stub->Articles);

        $result = $stub->fetchModel('Comments');
        $this->assertInstanceOf(Table::class, $result);
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
        $this->assertInstanceOf(CommentsTable::class, $result);
        $this->assertNull($stub->Comments);
    }

    /**
     * test alternate model factories.
     */
    public function testModelFactory(): void
    {
        $stub = new Stub();
        $stub->setProps('Articles');

        $table = new class extends Table {
            public function getAlias(): string
            {
                return 'Magic';
            }
        };

        $locator = new StubFactory();
        $locator->set('Magic', $table);
        $stub->modelFactory('Table', $locator);

        $result = $stub->fetchModel('Magic', 'Table');
        $this->assertInstanceOf(RepositoryInterface::class, $result);
        $this->assertSame('Magic', $result->getAlias());

        $locator = new StubFactory();
        $table2 = new class extends Table {
            public function getAlias(): string
            {
                return 'Foo';
            }
        };
        $locator->set('Foo', $table2);

        $stub->modelFactory('MyType', $locator);
        $result = $stub->fetchModel('Foo', 'MyType');
        $this->assertInstanceOf(RepositoryInterface::class, $result);
        $this->assertSame('Foo', $result->getAlias());
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

        $stub->fetchModel('Magic', 'Test');
    }
}
