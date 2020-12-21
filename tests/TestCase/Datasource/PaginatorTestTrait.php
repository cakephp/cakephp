<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.9.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\PageOutOfBoundsException;
use Cake\Datasource\Paginator;
use Cake\Datasource\RepositoryInterface;

trait PaginatorTestTrait
{
    /**
     * @var \Cake\Datasource\Paginator
     */
    protected $Paginator;

    /**
     * @var \Cake\Datasource\RepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $Post;

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');

        $this->Paginator = new Paginator();

        $this->Post = $this->getMockRepository();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->getTableLocator()->clear();
    }

    /**
     * Test that non-numeric values are rejected for page, and limit
     *
     * @return void
     */
    public function testPageParamCasting()
    {
        $this->Post->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('Posts'));

        $query = $this->_getMockFindQuery();
        $this->Post->expects($this->any())
            ->method('find')
            ->will($this->returnValue($query));

        $params = ['page' => '1 " onclick="alert(\'xss\');">'];
        $settings = ['limit' => 1, 'maxLimit' => 10];
        $this->Paginator->paginate($this->Post, $params, $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertSame(1, $pagingParams['Posts']['page'], 'XSS exploit opened');
    }

    /**
     * test that unknown keys in the default settings are
     * passed to the find operations.
     *
     * @return void
     */
    public function testPaginateExtraParams()
    {
        $params = ['page' => '-1'];
        $settings = [
            'PaginatorPosts' => [
                'contain' => ['PaginatorAuthor'],
                'maxLimit' => 10,
                'group' => 'PaginatorPosts.published',
                'order' => ['PaginatorPosts.id' => 'ASC'],
            ],
        ];
        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'contain' => ['PaginatorAuthor'],
                'group' => 'PaginatorPosts.published',
                'limit' => 10,
                'order' => ['PaginatorPosts.id' => 'ASC'],
                'page' => 1,
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sort' => 'PaginatorPosts.id',
            ]);
        $this->Paginator->paginate($table, $params, $settings);
    }

    /**
     * Test to make sure options get sent to custom finder methods via paginate
     *
     * @return void
     */
    public function testPaginateCustomFinderOptions()
    {
        $this->loadFixtures('Posts');
        $settings = [
            'PaginatorPosts' => [
                'finder' => ['author' => ['author_id' => 1]],
            ],
        ];
        $table = $this->getTableLocator()->get('PaginatorPosts');

        $expected = $table
            ->find('author', [
                'conditions' => [
                    'PaginatorPosts.author_id' => 1,
                ],
            ])
            ->count();
        $result = $this->Paginator->paginate($table, [], $settings)->count();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that nested eager loaders don't trigger invalid SQL errors.
     *
     * @return void
     */
    public function testPaginateNestedEagerLoader()
    {
        $this->loadFixtures('Articles', 'Tags', 'Authors', 'ArticlesTags', 'AuthorsTags');
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags');
        $tags = $this->getTableLocator()->get('Tags');
        $tags->belongsToMany('Authors');
        $articles->getEventManager()->on('Model.beforeFind', function ($event, $query) {
            $query ->matching('Tags', function ($q) {
                return $q->matching('Authors', function ($q) {
                    return $q->where(['Authors.name' => 'larry']);
                });
            });
        });
        $results = $this->Paginator->paginate($articles);
        $result = $results->first();
        $this->assertInstanceOf(EntityInterface::class, $result);
        $this->assertInstanceOf(EntityInterface::class, $result->_matchingData['Tags']);
        $this->assertInstanceOf(EntityInterface::class, $result->_matchingData['Authors']);
    }

    /**
     * test that flat default pagination parameters work.
     *
     * @return void
     */
    public function testDefaultPaginateParams()
    {
        $settings = [
            'order' => ['PaginatorPosts.id' => 'DESC'],
            'maxLimit' => 10,
        ];

        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'limit' => 10,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'DESC'],
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sort' => 'PaginatorPosts.id',
            ]);

        $this->Paginator->paginate($table, [], $settings);
    }

    /**
     * Tests that flat default pagination parameters work for multi order.
     *
     * @return void
     */
    public function testDefaultPaginateParamsMultiOrder()
    {
        $settings = [
            'order' => ['PaginatorPosts.id' => 'DESC', 'PaginatorPosts.title' => 'ASC'],
        ];

        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => $settings['order'],
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sort' => null,
            ]);

        $this->Paginator->paginate($table, [], $settings);

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertNull($pagingParams['PaginatorPosts']['direction']);
        $this->assertFalse($pagingParams['PaginatorPosts']['sortDefault']);
        $this->assertFalse($pagingParams['PaginatorPosts']['directionDefault']);
    }

    /**
     * test that default sort and default direction are injected into request
     *
     * @return void
     */
    public function testDefaultPaginateParamsIntoRequest()
    {
        $settings = [
            'order' => ['PaginatorPosts.id' => 'DESC'],
            'maxLimit' => 10,
        ];

        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'limit' => 10,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'DESC'],
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sort' => 'PaginatorPosts.id',
            ]);

        $this->Paginator->paginate($table, [], $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('PaginatorPosts.id', $pagingParams['PaginatorPosts']['sortDefault']);
        $this->assertEquals('DESC', $pagingParams['PaginatorPosts']['directionDefault']);
    }

    /**
     * test that option merging prefers specific models
     *
     * @return void
     */
    public function testMergeOptionsModelSpecific()
    {
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'Posts' => [
                'page' => 1,
                'limit' => 10,
                'maxLimit' => 50,
            ],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
        ];
        $defaults = $this->Paginator->getDefaults('Silly', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $this->assertEquals($settings, $result);

        $defaults = $this->Paginator->getDefaults('Posts', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 10,
            'maxLimit' => 50,
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test mergeOptions with custom scope
     *
     * @return void
     */
    public function testMergeOptionsCustomScope()
    {
        $params = [
            'page' => 10,
            'limit' => 10,
            'scope' => [
                'page' => 2,
                'limit' => 5,
            ],
        ];

        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 10,
            'limit' => 10,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'scope' => 'nonexistent',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'scope' => 'nonexistent',
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'scope' => 'scope',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 2,
            'limit' => 5,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
            'scope' => 'scope',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test mergeOptions with customFind key
     *
     * @return void
     */
    public function testMergeOptionsCustomFindKey()
    {
        $params = [
            'page' => 10,
            'limit' => 10,
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 10,
            'limit' => 10,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test merging options from the querystring.
     *
     * @return void
     */
    public function testMergeOptionsQueryString()
    {
        $params = [
            'page' => 99,
            'limit' => 75,
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 99,
            'limit' => 75,
            'maxLimit' => 100,
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that the default allowedParameters doesn't let people screw with things they should not be allowed to.
     *
     * @return void
     */
    public function testMergeOptionsDefaultAllowedParameters()
    {
        $params = [
            'page' => 10,
            'limit' => 10,
            'fields' => ['bad.stuff'],
            'recursive' => 1000,
            'conditions' => ['bad.stuff'],
            'contain' => ['bad'],
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 10,
            'limit' => 10,
            'maxLimit' => 100,
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that modifying the deprecated whitelist works.
     *
     * @return void
     */
    public function testMergeOptionsExtraWhitelist()
    {
        $params = [
            'page' => 10,
            'limit' => 10,
            'fields' => ['bad.stuff'],
            'recursive' => 1000,
            'conditions' => ['bad.stuff'],
            'contain' => ['bad'],
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $this->deprecated(function () use ($settings, $params) {
            $this->Paginator->setConfig('whitelist', ['fields']);
            $defaults = $this->Paginator->getDefaults('Post', $settings);
            $result = $this->Paginator->mergeOptions($params, $defaults);
            $expected = [
                'page' => 10,
                'limit' => 10,
                'maxLimit' => 100,
                'fields' => ['bad.stuff'],
                'whitelist' => ['limit', 'sort', 'page', 'direction', 'fields'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction', 'fields'],
            ];
            $this->assertEquals($expected, $result);
        });
    }

    /**
     * test mergeOptions with limit > maxLimit in code.
     *
     * @return void
     */
    public function testMergeOptionsMaxLimit()
    {
        $settings = [
            'limit' => 200,
            'paramType' => 'named',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 100,
            'maxLimit' => 100,
            'paramType' => 'named',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'maxLimit' => 10,
            'paramType' => 'named',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 10,
            'maxLimit' => 10,
            'paramType' => 'named',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test getDefaults with limit > maxLimit in code.
     *
     * @return void
     */
    public function testGetDefaultMaxLimit()
    {
        $settings = [
            'page' => 1,
            'limit' => 2,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 2,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'page' => 1,
            'limit' => 100,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 10,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Integration test to ensure that validateSort is being used by paginate()
     *
     * @return void
     */
    public function testValidateSortInvalid()
    {
        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'asc'],
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sort' => 'id',
            ]);

        $params = [
            'page' => 1,
            'sort' => 'id',
            'direction' => 'herp',
        ];
        $this->Paginator->paginate($table, $params);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('id', $pagingParams['PaginatorPosts']['sort']);
        $this->assertEquals('asc', $pagingParams['PaginatorPosts']['direction']);
    }

    /**
     * test that invalid directions are ignored.
     *
     * @return void
     */
    public function testValidateSortInvalidDirection()
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())
            ->method('hasField')
            ->will($this->returnValue(true));

        $options = ['sort' => 'something', 'direction' => 'boogers'];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertEquals('asc', $result['order']['model.something']);
    }

    /**
     * Test that "sort" and "direction" in paging params is properly set based
     * on initial value of "order" in paging settings.
     *
     * @return void
     */
    public function testValidaSortInitialSortAndDirection()
    {
        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'asc'],
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'sort' => 'id',
                'scope' => null,
                'sortWhitelist' => ['id'],
                'sortableFields' => ['id'],
            ]);

        $options = [
            'order' => [
                'id' => 'asc',
            ],
            'sortableFields' => ['id'],
        ];
        $this->Paginator->paginate($table, [], $options);
        $pagingParams = $this->Paginator->getPagingParams();

        $this->assertEquals('id', $pagingParams['PaginatorPosts']['sort']);
        $this->assertEquals('asc', $pagingParams['PaginatorPosts']['direction']);
    }

    /**
     * Test that "sort" and "direction" in paging params is properly set based
     * on initial value of "order" in paging settings.
     *
     * @return void
     */
    public function testValidateSortAndDirectionAliased()
    {
        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => ['PaginatorPosts.title' => 'asc'],
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'sort' => 'title',
                'scope' => null,
            ]);

        $options = [
            'order' => [
                'Articles.title' => 'desc',
            ],
        ];
        $queryParams = [
            'page' => 1,
            'sort' => 'title',
            'direction' => 'asc',
        ];

        $this->Paginator->paginate($table, $queryParams, $options);
        $pagingParams = $this->Paginator->getPagingParams();

        $this->assertEquals('title', $pagingParams['PaginatorPosts']['sort']);
        $this->assertEquals('asc', $pagingParams['PaginatorPosts']['direction']);

        $this->assertEquals('Articles.title', $pagingParams['PaginatorPosts']['sortDefault']);
        $this->assertEquals('desc', $pagingParams['PaginatorPosts']['directionDefault']);
    }

    /**
     * testValidateSortRetainsOriginalSortValue
     *
     * @return void
     * @see https://github.com/cakephp/cakephp/issues/11740
     */
    public function testValidateSortRetainsOriginalSortValue()
    {
        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 20,
                'page' => 1,
                'order' => ['PaginatorPosts.id' => 'asc'],
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sortWhitelist' => ['id'],
                'sortableFields' => ['id'],
                'sort' => 'id',
            ]);

        $params = [
            'page' => 1,
            'sort' => 'id',
            'direction' => 'herp',
        ];
        $options = [
            'sortableFields' => ['id'],
        ];
        $this->Paginator->paginate($table, $params, $options);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('id', $pagingParams['PaginatorPosts']['sort']);
    }

    /**
     * Test that a really large page number gets clamped to the max page size.
     *
     * @return void
     */
    public function testOutOfRangePageNumberGetsClamped()
    {
        $this->loadFixtures('Posts');
        $params['page'] = 3000;

        $table = $this->getTableLocator()->get('PaginatorPosts');
        try {
            $this->Paginator->paginate($table, $params);
            $this->fail('No exception raised');
        } catch (PageOutOfBoundsException $exception) {
            $this->assertEquals(
                'Page number 3000 could not be found.',
                $exception->getMessage()
            );

            $this->assertSame(
                [
                    'requestedPage' => 3000,
                    'pagingParams' => $this->Paginator->getPagingParams(),
                ],
                $exception->getAttributes()
            );
        }
    }

    /**
     * Test that a really REALLY large page number gets clamped to the max page size.
     *
     * @return void
     */
    public function testOutOfVeryBigPageNumberGetsClamped()
    {
        $this->expectException(\Cake\Datasource\Exception\PageOutOfBoundsException::class);
        $this->loadFixtures('Posts');
        $params = [
            'page' => '3000000000000000000000000',
        ];

        $table = $this->getTableLocator()->get('PaginatorPosts');
        $this->Paginator->paginate($table, $params);
    }

    /**
     * test that fields not in sortableFields won't be part of order conditions.
     *
     * @return void
     */
    public function testValidateAllowedSortFailure()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'sort' => 'body',
            'direction' => 'asc',
            'sortableFields' => ['title', 'id'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertEquals([], $result['order']);
    }

    /**
     * test that fields not in whitelist won't be part of order conditions.
     *
     * @return void
     */
    public function testValidateSortWhitelistFailure()
    {
        $this->deprecated(function () {
            $model = $this->mockAliasHasFieldModel();
            $options = [
                'sort' => 'body',
                'direction' => 'asc',
                'sortWhitelist' => ['title', 'id'],
            ];
            $result = $this->Paginator->validateSort($model, $options);
            $this->assertEquals([], $result['order']);
        });
    }

    /**
     * test that fields in the sortableFields are not validated
     *
     * @return void
     */
    public function testValidateAllowedSortTrusted()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'sort' => 'body',
            'direction' => 'asc',
            'allowedsort' => ['body'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = ['model.body' => 'asc'];
        $this->assertEquals(
            $expected,
            $result['order'],
            'Trusted fields in schema should be prefixed'
        );
    }

    /**
     * test that sortableFields as empty array does not allow any sorting
     *
     * @return void
     */
    public function testValidateAllowedSortEmpty()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'order' => [
                'body' => 'asc',
                'foo.bar' => 'asc',
            ],
            'sort' => 'body',
            'direction' => 'asc',
            'sortableFields' => [],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertSame([], $result['order'], 'No sort should be applied');
    }

    /**
     * test that fields in the sortableFields are not validated
     *
     * @return void
     */
    public function testValidateAllowedSortNotInSchema()
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->once())->method('hasField')
            ->will($this->returnValue(false));

        $options = [
            'sort' => 'score',
            'direction' => 'asc',
            'sortableFields' => ['score'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = ['score' => 'asc'];
        $this->assertEquals(
            $expected,
            $result['order'],
            'Trusted fields not in schema should not be altered'
        );
    }

    /**
     * test that multiple fields in the sortableFields are not validated and properly aliased.
     *
     * @return void
     */
    public function testValidateAllowedSortMultiple()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'order' => [
                'body' => 'asc',
                'foo.bar' => 'asc',
            ],
            'sortableFields' => ['body', 'foo.bar'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'model.body' => 'asc',
            'foo.bar' => 'asc',
        ];
        $this->assertEquals($expected, $result['order']);
    }

    /**
     * @return \Cake\Datasource\RepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockRepository()
    {
        $model = $this->getMockBuilder(RepositoryInterface::class)
            ->getMock();

        return $model;
    }

    /**
     * @param string $modelAlias Model alias to use.
     * @return \Cake\Datasource\RepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockAliasHasFieldModel($modelAlias = 'model')
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue($modelAlias));
        $model->expects($this->any())
            ->method('hasField')
            ->will($this->returnValue(true));

        return $model;
    }

    /**
     * test that multiple sort works.
     *
     * @return void
     */
    public function testValidateSortMultiple()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'order' => [
                'author_id' => 'asc',
                'title' => 'asc',
            ],
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $expected = [
            'model.author_id' => 'asc',
            'model.title' => 'asc',
        ];

        $this->assertEquals($expected, $result['order']);
    }

    /**
     * test that multiple sort adds in query data.
     *
     * @return void
     */
    public function testValidateSortMultipleWithQuery()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'sort' => 'created',
            'direction' => 'desc',
            'order' => [
                'author_id' => 'asc',
                'title' => 'asc',
            ],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'model.created' => 'desc',
            'model.author_id' => 'asc',
            'model.title' => 'asc',
        ];
        $this->assertEquals($expected, $result['order']);

        $options = [
            'sort' => 'title',
            'direction' => 'desc',
            'order' => [
                'author_id' => 'asc',
                'title' => 'asc',
            ],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'model.title' => 'desc',
            'model.author_id' => 'asc',
        ];
        $this->assertEquals($expected, $result['order']);
    }

    /**
     * Tests that sort query string and model prefixes default match on assoc merging.
     *
     * @return void
     */
    public function testValidateSortMultipleWithQueryAndAliasedDefault()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'sort' => 'created',
            'direction' => 'desc',
            'order' => [
                'model.created' => 'asc',
            ],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'sort' => 'created',
            'order' => [
                'model.created' => 'desc',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests that order strings can used by Paginator
     *
     * @return void
     */
    public function testValidateSortWithString()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'order' => 'model.author_id DESC',
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $expected = 'model.author_id DESC';

        $this->assertEquals($expected, $result['order']);
    }

    /**
     * Test that no sort doesn't trigger an error.
     *
     * @return void
     */
    public function testValidateSortNoSort()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = [
            'direction' => 'asc',
            'sortableFields' => ['title', 'id'],
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $this->assertEquals([], $result['order']);
    }

    /**
     * Test sorting with incorrect aliases on valid fields.
     *
     * @return void
     */
    public function testValidateSortInvalidAlias()
    {
        $model = $this->mockAliasHasFieldModel();

        $options = ['sort' => 'Derp.id'];
        $result = $this->Paginator->validateSort($model, $options);
        $this->assertEquals([], $result['order']);
    }

    /**
     * @return array
     */
    public function checkLimitProvider()
    {
        return [
            'out of bounds' => [
                ['limit' => 1000000, 'maxLimit' => 100],
                100,
            ],
            'limit is nan' => [
                ['limit' => 'sheep!', 'maxLimit' => 100],
                1,
            ],
            'negative limit' => [
                ['limit' => '-1', 'maxLimit' => 100],
                1,
            ],
            'unset limit' => [
                ['limit' => null, 'maxLimit' => 100],
                1,
            ],
            'limit = 0' => [
                ['limit' => 0, 'maxLimit' => 100],
                1,
            ],
            'limit = 0 v2' => [
                ['limit' => 0, 'maxLimit' => 0],
                1,
            ],
            'limit = null' => [
                ['limit' => null, 'maxLimit' => 0],
                1,
            ],
            'bad input, results in 1' => [
                ['limit' => null, 'maxLimit' => null],
                1,
            ],
            'bad input, results in 1 v2' => [
                ['limit' => false, 'maxLimit' => false],
                1,
            ],
        ];
    }

    /**
     * test that maxLimit is respected
     *
     * @dataProvider checkLimitProvider
     * @return void
     */
    public function testCheckLimit($input, $expected)
    {
        $result = $this->Paginator->checkLimit($input);
        $this->assertSame($expected, $result['limit']);
    }

    /**
     * Integration test for checkLimit() being applied inside paginate()
     *
     * @return void
     */
    public function testPaginateMaxLimit()
    {
        $this->loadFixtures('Posts');
        $table = $this->getTableLocator()->get('PaginatorPosts');

        $settings = [
            'maxLimit' => 100,
        ];
        $params = [
            'limit' => '1000',
        ];
        $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(100, $pagingParams['PaginatorPosts']['limit']);
        $this->assertEquals(100, $pagingParams['PaginatorPosts']['perPage']);

        $params = [
            'limit' => '10',
        ];
        $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(10, $pagingParams['PaginatorPosts']['limit']);
        $this->assertEquals(10, $pagingParams['PaginatorPosts']['perPage']);
    }

    /**
     * test paginate() and custom finders to ensure the count + find
     * use the custom type.
     *
     * @return void
     */
    public function testPaginateCustomFindCount()
    {
        $settings = [
            'finder' => 'published',
            'limit' => 2,
        ];
        $table = $this->_getMockPosts(['query']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('applyOptions')
            ->with([
                'limit' => 2,
                'page' => 1,
                'order' => [],
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sort' => null,
            ]);
        $this->Paginator->paginate($table, [], $settings);
    }

    /**
     * Tests that it is possible to pass an already made query object to
     * paginate()
     *
     * @return void
     */
    public function testPaginateQuery()
    {
        $params = ['page' => '-1'];
        $settings = [
            'PaginatorPosts' => [
                'contain' => ['PaginatorAuthor'],
                'maxLimit' => 10,
                'group' => 'PaginatorPosts.published',
                'order' => ['PaginatorPosts.id' => 'ASC'],
            ],
        ];
        $table = $this->_getMockPosts(['find']);
        $query = $this->_getMockFindQuery($table);
        $table->expects($this->never())->method('find');
        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'contain' => ['PaginatorAuthor'],
                'group' => 'PaginatorPosts.published',
                'limit' => 10,
                'order' => ['PaginatorPosts.id' => 'ASC'],
                'page' => 1,
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sort' => 'PaginatorPosts.id',
            ]);
        $this->Paginator->paginate($query, $params, $settings);
    }

    /**
     * test paginate() with bind()
     *
     * @return void
     */
    public function testPaginateQueryWithBindValue()
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(strpos($config['driver'], 'Sqlserver') !== false, 'Test temporarily broken in SQLServer');
        $this->loadFixtures('Posts');
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $query = $table->find()
            ->where(['PaginatorPosts.author_id BETWEEN :start AND :end'])
            ->bind(':start', 1)
            ->bind(':end', 2);

        $results = $this->Paginator->paginate($query, []);

        $result = $results->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals('First Post', $result[0]->title);
        $this->assertEquals('Third Post', $result[1]->title);
    }

    /**
     * Tests that passing a query object with a limit clause set will
     * overwrite it with the passed defaults.
     *
     * @return void
     */
    public function testPaginateQueryWithLimit()
    {
        $params = ['page' => '-1'];
        $settings = [
            'PaginatorPosts' => [
                'contain' => ['PaginatorAuthor'],
                'maxLimit' => 10,
                'limit' => 5,
                'group' => 'PaginatorPosts.published',
                'order' => ['PaginatorPosts.id' => 'ASC'],
            ],
        ];
        $table = $this->_getMockPosts(['find']);
        $query = $this->_getMockFindQuery($table);
        $query->limit(2);
        $table->expects($this->never())->method('find');
        $query->expects($this->once())
            ->method('applyOptions')
            ->with([
                'contain' => ['PaginatorAuthor'],
                'group' => 'PaginatorPosts.published',
                'limit' => 5,
                'order' => ['PaginatorPosts.id' => 'ASC'],
                'page' => 1,
                'whitelist' => ['limit', 'sort', 'page', 'direction'],
                'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
                'scope' => null,
                'sort' => 'PaginatorPosts.id',
            ]);
        $this->Paginator->paginate($query, $params, $settings);
    }

    /**
     * Helper method for making mocks.
     *
     * @param array $methods
     * @return \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function _getMockPosts($methods = [])
    {
        return $this->getMockBuilder('TestApp\Model\Table\PaginatorPostsTable')
            ->onlyMethods($methods)
            ->setConstructorArgs([[
                'connection' => ConnectionManager::get('test'),
                'alias' => 'PaginatorPosts',
                'schema' => [
                    'id' => ['type' => 'integer'],
                    'author_id' => ['type' => 'integer', 'null' => false],
                    'title' => ['type' => 'string', 'null' => false],
                    'body' => 'text',
                    'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
                    '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
                ],
            ]])
            ->getMock();
    }

    /**
     * Helper method for mocking queries.
     *
     * @param string|null $table
     * @return \Cake\ORM\Query|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function _getMockFindQuery($table = null)
    {
        /** @var \Cake\ORM\Query|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->onlyMethods(['all', 'count', 'applyOptions'])
            ->addMethods(['total'])
            ->disableOriginalConstructor()
            ->getMock();

        $results = $this->getMockBuilder('Cake\ORM\ResultSet')
            ->disableOriginalConstructor()
            ->getMock();

        $query->expects($this->any())
            ->method('count')
            ->will($this->returnValue(2));

        $query->expects($this->any())
            ->method('all')
            ->will($this->returnValue($results));

        if ($table) {
            $query->repository($table);
        }

        return $query;
    }
}
