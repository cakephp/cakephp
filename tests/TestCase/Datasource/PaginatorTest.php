<?php
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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\PageOutOfBoundsException;
use Cake\Datasource\Paginator;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class PaginatorTest extends TestCase
{

    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'core.posts', 'core.articles', 'core.articles_tags',
        'core.authors', 'core.authors_tags', 'core.tags'
    ];

    /**
     * Don't load data for fixtures for all tests
     *
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');

        $this->Paginator = new Paginator();

        $this->Post = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Test that non-numeric values are rejected for page, and limit
     *
     * @return void
     */
    public function testPageParamCasting()
    {
        $this->Post->expects($this->any())
            ->method('alias')
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
                'order' => ['PaginatorPosts.id' => 'ASC']
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
                'scope' => null,
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
                'finder' => ['author' => ['author_id' => 1]]
            ]
        ];
        $table = TableRegistry::get('PaginatorPosts');

        $expected = $table
            ->find('author', [
                'conditions' => [
                    'PaginatorPosts.author_id' => 1
                ]
            ])
            ->count();
        $result = $this->Paginator->paginate($table, [], $settings)->count();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that special paginate types are called and that the type param doesn't leak out into defaults or options.
     *
     * @return void
     */
    public function testPaginateCustomFinder()
    {
        $settings = [
            'PaginatorPosts' => [
                'finder' => 'popular',
                'fields' => ['id', 'title'],
                'maxLimit' => 10,
            ]
        ];

        $table = $this->_getMockPosts(['findPopular']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->any())
            ->method('findPopular')
            ->will($this->returnValue($query));

        $this->Paginator->paginate($table, [], $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('popular', $pagingParams['PaginatorPosts']['finder']);
    }

    /**
     * Test that nested eager loaders don't trigger invalid SQL errors.
     *
     * @return void
     */
    public function testPaginateNestedEagerLoader()
    {
        $this->loadFixtures('Articles', 'Tags', 'Authors', 'ArticlesTags', 'AuthorsTags');
        $articles = TableRegistry::get('Articles');
        $articles->belongsToMany('Tags');
        $tags = TableRegistry::get('Tags');
        $tags->belongsToMany('Authors');
        $articles->eventManager()->on('Model.beforeFind', function ($event, $query) {
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
                'scope' => null,
            ]);

        $this->Paginator->paginate($table, [], $settings);
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
                'scope' => null,
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
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
        ];
        $defaults = $this->Paginator->getDefaults('Silly', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $this->assertEquals($settings, $result);

        $defaults = $this->Paginator->getDefaults('Posts', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = ['page' => 1, 'limit' => 10, 'maxLimit' => 50, 'whitelist' => ['limit', 'sort', 'page', 'direction']];
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
            ]
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
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'scope' => 'non-existent',
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'scope' => 'non-existent',
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
            'limit' => 10
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind'
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 10,
            'limit' => 10,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
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
            'limit' => 75
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = ['page' => 99, 'limit' => 75, 'maxLimit' => 100, 'whitelist' => ['limit', 'sort', 'page', 'direction']];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that the default whitelist doesn't let people screw with things they should not be allowed to.
     *
     * @return void
     */
    public function testMergeOptionsDefaultWhiteList()
    {
        $params = [
            'page' => 10,
            'limit' => 10,
            'fields' => ['bad.stuff'],
            'recursive' => 1000,
            'conditions' => ['bad.stuff'],
            'contain' => ['bad']
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = ['page' => 10, 'limit' => 10, 'maxLimit' => 100, 'whitelist' => ['limit', 'sort', 'page', 'direction']];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that modifying the whitelist works.
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
            'contain' => ['bad']
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $this->Paginator->config('whitelist', ['fields']);
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions($params, $defaults);
        $expected = [
            'page' => 10, 'limit' => 10, 'maxLimit' => 100, 'fields' => ['bad.stuff'], 'whitelist' => ['limit', 'sort', 'page', 'direction', 'fields']
        ];
        $this->assertEquals($expected, $result);
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
            'whitelist' => ['limit', 'sort', 'page', 'direction']
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
            'whitelist' => ['limit', 'sort', 'page', 'direction']
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
                'Users.username' => 'asc'
            ],
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 2,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc'
            ],
            'whitelist' => ['limit', 'sort', 'page', 'direction']
        ];
        $this->assertEquals($expected, $result);

        $settings = [
            'page' => 1,
            'limit' => 100,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc'
            ],
        ];
        $defaults = $this->Paginator->getDefaults('Post', $settings);
        $result = $this->Paginator->mergeOptions([], $defaults);
        $expected = [
            'page' => 1,
            'limit' => 10,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc'
            ],
            'whitelist' => ['limit', 'sort', 'page', 'direction']
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
                'scope' => null,
            ]);

        $params = [
            'page' => 1,
            'sort' => 'id',
            'direction' => 'herp'
        ];
        $this->Paginator->paginate($table, $params);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals('PaginatorPosts.id', $pagingParams['PaginatorPosts']['sort']);
        $this->assertEquals('asc', $pagingParams['PaginatorPosts']['direction']);
    }

    /**
     * test that invalid directions are ignored.
     *
     * @return void
     */
    public function testValidateSortInvalidDirection()
    {
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())
            ->method('hasField')
            ->will($this->returnValue(true));

        $options = ['sort' => 'something', 'direction' => 'boogers'];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertEquals('asc', $result['order']['model.something']);
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

        $table = TableRegistry::get('PaginatorPosts');
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
                    'pagingParams' => $this->Paginator->getPagingParams()
                ],
                $exception->getAttributes()
            );
        }
    }

    /**
     * Test that a really REALLY large page number gets clamped to the max page size.
     *
     * @expectedException \Cake\Datasource\Exception\PageOutOfBoundsException
     * @return void
     */
    public function testOutOfVeryBigPageNumberGetsClamped()
    {
        $this->loadFixtures('Posts');
        $params = [
            'page' => '3000000000000000000000000',
        ];

        $table = TableRegistry::get('PaginatorPosts');
        $this->Paginator->paginate($table, $params);
    }

    /**
     * test that fields not in whitelist won't be part of order conditions.
     *
     * @return void
     */
    public function testValidateSortWhitelistFailure()
    {
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')->will($this->returnValue(true));

        $options = [
            'sort' => 'body',
            'direction' => 'asc',
            'sortWhitelist' => ['title', 'id']
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertEquals([], $result['order']);
    }

    /**
     * test that fields in the whitelist are not validated
     *
     * @return void
     */
    public function testValidateSortWhitelistTrusted()
    {
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->once())
            ->method('hasField')
            ->will($this->returnValue(true));

        $options = [
            'sort' => 'body',
            'direction' => 'asc',
            'sortWhitelist' => ['body']
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
     * test that whitelist as empty array does not allow any sorting
     *
     * @return void
     */
    public function testValidateSortWhitelistEmpty()
    {
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')
            ->will($this->returnValue(true));

        $options = [
            'order' => [
                'body' => 'asc',
                'foo.bar' => 'asc'
            ],
            'sort' => 'body',
            'direction' => 'asc',
            'sortWhitelist' => []
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertSame([], $result['order'], 'No sort should be applied');
    }

    /**
     * test that fields in the whitelist are not validated
     *
     * @return void
     */
    public function testValidateSortWhitelistNotInSchema()
    {
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->once())->method('hasField')
            ->will($this->returnValue(false));

        $options = [
            'sort' => 'score',
            'direction' => 'asc',
            'sortWhitelist' => ['score']
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
     * test that multiple fields in the whitelist are not validated and properly aliased.
     *
     * @return void
     */
    public function testValidateSortWhitelistMultiple()
    {
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->once())
            ->method('hasField')
            ->will($this->returnValue(true));

        $options = [
            'order' => [
                'body' => 'asc',
                'foo.bar' => 'asc'
            ],
            'sortWhitelist' => ['body', 'foo.bar']
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $expected = [
            'model.body' => 'asc',
            'foo.bar' => 'asc'
        ];
        $this->assertEquals($expected, $result['order']);
    }

    /**
     * test that multiple sort works.
     *
     * @return void
     */
    public function testValidateSortMultiple()
    {
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')->will($this->returnValue(true));

        $options = [
            'order' => [
                'author_id' => 'asc',
                'title' => 'asc'
            ]
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $expected = [
            'model.author_id' => 'asc',
            'model.title' => 'asc'
        ];

        $this->assertEquals($expected, $result['order']);
    }

    /**
     * Tests that order strings can used by Paginator
     *
     * @return void
     */
    public function testValidateSortWithString()
    {
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')->will($this->returnValue(true));

        $options = [
            'order' => 'model.author_id DESC'
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
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')
            ->will($this->returnValue(true));

        $options = [
            'direction' => 'asc',
            'sortWhitelist' => ['title', 'id'],
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
        $model = $this->getMockBuilder('Cake\Datasource\RepositoryInterface')->getMock();
        $model->expects($this->any())
            ->method('alias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')->will($this->returnValue(true));

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
        $table = TableRegistry::get('PaginatorPosts');

        $settings = [
            'maxLimit' => 100,
        ];
        $params = [
            'limit' => '1000'
        ];
        $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(100, $pagingParams['PaginatorPosts']['limit']);
        $this->assertEquals(100, $pagingParams['PaginatorPosts']['perPage']);

        $params = [
            'limit' => '10'
        ];
        $this->Paginator->paginate($table, $params, $settings);
        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(10, $pagingParams['PaginatorPosts']['limit']);
        $this->assertEquals(10, $pagingParams['PaginatorPosts']['perPage']);
    }

    /**
     * test paginate() and custom find, to make sure the correct count is returned.
     *
     * @return void
     */
    public function testPaginateCustomFind()
    {
        $this->loadFixtures('Posts');
        $titleExtractor = function ($result) {
            $ids = [];
            foreach ($result as $record) {
                $ids[] = $record->title;
            }

            return $ids;
        };

        $table = TableRegistry::get('PaginatorPosts');
        $data = ['author_id' => 3, 'title' => 'Fourth Post', 'body' => 'Article Body, unpublished', 'published' => 'N'];
        $result = $table->save(new Entity($data));
        $this->assertNotEmpty($result);

        $result = $this->Paginator->paginate($table);
        $this->assertCount(4, $result, '4 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post', 'Fourth Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(4, $pagingParams['PaginatorPosts']['current']);
        $this->assertEquals(4, $pagingParams['PaginatorPosts']['count']);

        $settings = ['finder' => 'published'];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(3, $result, '3 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(3, $pagingParams['PaginatorPosts']['current']);
        $this->assertEquals(3, $pagingParams['PaginatorPosts']['count']);

        $settings = ['finder' => 'published', 'limit' => 2, 'page' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(1, $result, '1 rows should come back');
        $this->assertEquals(['Third Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(1, $pagingParams['PaginatorPosts']['current']);
        $this->assertEquals(3, $pagingParams['PaginatorPosts']['count']);
        $this->assertEquals(2, $pagingParams['PaginatorPosts']['pageCount']);

        $settings = ['finder' => 'published', 'limit' => 2];
        $result = $this->Paginator->paginate($table, [], $settings);
        $this->assertCount(2, $result, '2 rows should come back');
        $this->assertEquals(['First Post', 'Second Post'], $titleExtractor($result));

        $pagingParams = $this->Paginator->getPagingParams();
        $this->assertEquals(2, $pagingParams['PaginatorPosts']['current']);
        $this->assertEquals(3, $pagingParams['PaginatorPosts']['count']);
        $this->assertEquals(2, $pagingParams['PaginatorPosts']['pageCount']);
        $this->assertTrue($pagingParams['PaginatorPosts']['nextPage']);
        $this->assertFalse($pagingParams['PaginatorPosts']['prevPage']);
        $this->assertEquals(2, $pagingParams['PaginatorPosts']['perPage']);
        $this->assertNull($pagingParams['PaginatorPosts']['limit']);
    }

    /**
     * test paginate() and custom find with fields array, to make sure the correct count is returned.
     *
     * @return void
     */
    public function testPaginateCustomFindFieldsArray()
    {
        $this->loadFixtures('Posts');
        $table = TableRegistry::get('PaginatorPosts');
        $data = ['author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N'];
        $table->save(new Entity($data));

        $settings = [
            'finder' => 'list',
            'conditions' => ['PaginatorPosts.published' => 'Y'],
            'limit' => 2
        ];
        $results = $this->Paginator->paginate($table, [], $settings);

        $result = $results->toArray();
        $expected = [
            1 => 'First Post',
            2 => 'Second Post',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->Paginator->getPagingParams()['PaginatorPosts'];
        $this->assertEquals(2, $result['current']);
        $this->assertEquals(3, $result['count']);
        $this->assertEquals(2, $result['pageCount']);
        $this->assertTrue($result['nextPage']);
        $this->assertFalse($result['prevPage']);
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
            'limit' => 2
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
                'scope' => null,
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
                'order' => ['PaginatorPosts.id' => 'ASC']
            ]
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
                'scope' => null,
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
        $config = ConnectionManager::config('test');
        $this->skipIf(strpos($config['driver'], 'Sqlserver') !== false, 'Test temporarily broken in SQLServer');
        $this->loadFixtures('Posts');
        $table = TableRegistry::get('PaginatorPosts');
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
                'order' => ['PaginatorPosts.id' => 'ASC']
            ]
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
                'scope' => null,
            ]);
        $this->Paginator->paginate($query, $params, $settings);
    }

    /**
     * Helper method for making mocks.
     *
     * @param array $methods
     * @return \Cake\ORM\Table
     */
    protected function _getMockPosts($methods = [])
    {
        return $this->getMockBuilder('TestApp\Model\Table\PaginatorPostsTable')
            ->setMethods($methods)
            ->setConstructorArgs([[
                'connection' => ConnectionManager::get('test'),
                'alias' => 'PaginatorPosts',
                'schema' => [
                    'id' => ['type' => 'integer'],
                    'author_id' => ['type' => 'integer', 'null' => false],
                    'title' => ['type' => 'string', 'null' => false],
                    'body' => 'text',
                    'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
                    '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
                ]
            ]])
            ->getMock();
    }

    /**
     * Helper method for mocking queries.
     *
     * @return \Cake\ORM\Query
     */
    protected function _getMockFindQuery($table = null)
    {
        $query = $this->getMockBuilder('Cake\ORM\Query')
            ->setMethods(['total', 'all', 'count', 'applyOptions'])
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

        $query->expects($this->any())
            ->method('count')
            ->will($this->returnValue(2));

        $query->repository($table);

        return $query;
    }
}
