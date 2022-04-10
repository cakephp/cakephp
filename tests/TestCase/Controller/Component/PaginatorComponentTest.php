<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\PaginatorComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Paging\Exception\PageOutOfBoundsException;
use Cake\Datasource\RepositoryInterface;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use stdClass;
use TestApp\Controller\Component\CustomPaginatorComponent;
use TestApp\Datasource\Paging\CustomPaginator;
use UnexpectedValueException;

class PaginatorComponentTest extends TestCase
{
    /**
     * fixtures property
     *
     * @var array<string>
     */
    protected $fixtures = [
        'core.Posts', 'core.Articles', 'core.ArticlesTags',
        'core.Tags', 'core.Authors', 'core.AuthorsTags',
    ];

    /**
     * @var \Cake\Controller\Controller
     */
    protected $controller;

    /**
     * @var \Cake\Controller\ComponentRegistry
     */
    protected $registry;

    /**
     * @var \Cake\Controller\Component\PaginatorComponent
     */
    protected $Paginator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $Post;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();

        static::setAppNamespace();

        $request = new ServerRequest(['url' => 'controller_posts/index']);
        $this->controller = new Controller($request);
        $this->registry = new ComponentRegistry($this->controller);
        $this->deprecated(function () {
            $this->Paginator = new PaginatorComponent($this->registry, []);
        });

        $this->Post = $this->getMockRepository();
    }

    /**
     * testPaginatorSetting
     */
    public function testPaginatorSetting(): void
    {
        $this->deprecated(function () {
            $paginator = new CustomPaginator();
            $component = new PaginatorComponent($this->registry, [
                'paginator' => $paginator,
            ]);

            $this->assertSame($paginator, $component->getPaginator());

            $component = new PaginatorComponent($this->registry, []);
            $this->assertNotSame($paginator, $component->getPaginator());

            $component->setPaginator($paginator);
            $this->assertSame($paginator, $component->getPaginator());
        });
    }

    public function testInvalidDefaultConfig(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->deprecated(function () {
            new CustomPaginatorComponent($this->registry);
        });
    }

    /**
     * Test that an exception is thrown when paginator option is invalid.
     */
    public function testInvalidPaginatorOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Paginator must be an instance of Cake\Datasource\Paging\NumericPaginator');
        $this->deprecated(function () {
            new PaginatorComponent($this->registry, [
                'paginator' => new stdClass(),
            ]);
        });
    }

    /**
     * Test that non-numeric values are rejected for page, and limit
     */
    public function testPageParamCasting(): void
    {
        $this->Post->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('Posts'));

        $query = $this->_getMockFindQuery();
        $this->Post->expects($this->any())
            ->method('find')
            ->will($this->returnValue($query));

        $this->controller->setRequest($this->controller->getRequest()->withQueryParams(['page' => '1 " onclick="alert(\'xss\');">']));
        $settings = ['limit' => 1, 'maxLimit' => 10];
        $this->Paginator->paginate($this->Post, $settings);
        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(1, $params['Posts']['page'], 'XSS exploit opened');
    }

    /**
     * test that unknown keys in the default settings are
     * passed to the find operations.
     */
    public function testPaginateExtraParams(): void
    {
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams(['page' => '-1']));
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
        $this->Paginator->paginate($table, $settings);
    }

    /**
     * Test to make sure options get sent to custom finder methods via paginate
     */
    public function testPaginateCustomFinderOptions(): void
    {
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
        $result = $this->Paginator->paginate($table, $settings)->count();

        $this->assertSame($expected, $result);
    }

    /**
     * testRequestParamsSetting
     *
     * @see https://github.com/cakephp/cakephp/issues/11655
     */
    public function testRequestParamsSetting(): void
    {
        $settings = [
            'PaginatorPosts' => [
                'limit' => 10,
            ],
        ];

        $table = $this->getTableLocator()->get('PaginatorPosts');

        $this->Paginator->paginate($table, $settings);
        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertArrayHasKey('PaginatorPosts', $params);
        $this->assertArrayNotHasKey(0, $params);
    }

    /**
     * Test that special paginate types are called and that the type param doesn't leak out into defaults or options.
     */
    public function testPaginateCustomFinder(): void
    {
        $settings = [
            'PaginatorPosts' => [
                'finder' => 'popular',
                'fields' => ['id', 'title'],
                'maxLimit' => 10,
            ],
        ];

        $table = $this->_getMockPosts(['findPopular']);
        $query = $this->_getMockFindQuery();

        $table->expects($this->any())
            ->method('findPopular')
            ->will($this->returnValue($query));

        $this->Paginator->paginate($table, $settings);
        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame('popular', $params['PaginatorPosts']['finder']);
    }

    /**
     * Test that nested eager loaders don't trigger invalid SQL errors.
     */
    public function testPaginateNestedEagerLoader(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->belongsToMany('Tags');
        $tags = $this->getTableLocator()->get('Tags');
        $tags->belongsToMany('Authors');

        $articles->getEventManager()->on('Model.beforeFind', function (EventInterface $event, Query $query): void {
            $query ->matching('Tags', function (Query $q) {
                return $q->matching('Authors', function (Query $q) {
                    return $q->where(['Authors.name' => 'larry']);
                });
            });
        });
        $results = $this->Paginator->paginate($articles, []);

        $result = $results->first();
        $this->assertInstanceOf(EntityInterface::class, $result);
        $this->assertInstanceOf(EntityInterface::class, $result->_matchingData['Tags']);
        $this->assertInstanceOf(EntityInterface::class, $result->_matchingData['Authors']);
    }

    /**
     * test that flat default pagination parameters work.
     */
    public function testDefaultPaginateParams(): void
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

        $this->Paginator->paginate($table, $settings);
    }

    /**
     * test that default sort and default direction are injected into request
     */
    public function testDefaultPaginateParamsIntoRequest(): void
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

        $this->Paginator->paginate($table, $settings);
        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame('PaginatorPosts.id', $params['PaginatorPosts']['sortDefault']);
        $this->assertSame('DESC', $params['PaginatorPosts']['directionDefault']);
    }

    /**
     * test that option merging prefers specific models
     */
    public function testMergeOptionsModelSpecific(): void
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
        ];
        $result = $this->Paginator->mergeOptions('Silly', $settings);
        $this->assertSame($settings['allowedParameters'], $result['whitelist']);
        unset($result['whitelist']);
        $this->assertEquals($settings, $result);

        $result = $this->Paginator->mergeOptions('Posts', $settings);
        $expected = [
            'page' => 1,
            'limit' => 10,
            'maxLimit' => 50,
            'whitelist' => ['limit', 'sort', 'page', 'direction'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test mergeOptions with custom scope
     */
    public function testMergeOptionsCustomScope(): void
    {
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'page' => 10,
            'limit' => 10,
            'scope' => [
                'page' => 2,
                'limit' => 5,
            ],
        ]));

        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
        ];
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
     */
    public function testMergeOptionsCustomFindKey(): void
    {
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'page' => 10,
            'limit' => 10,
        ]));
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind',
        ];
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
     */
    public function testMergeOptionsQueryString(): void
    {
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'page' => 99,
            'limit' => 75,
        ]));
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
     * test that the default allowed parameters doesn't let people screw with things they should not be allowed to.
     */
    public function testMergeOptionsDefaultAllowedParameters(): void
    {
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'page' => 10,
            'limit' => 10,
            'fields' => ['bad.stuff'],
            'recursive' => 1000,
            'conditions' => ['bad.stuff'],
            'contain' => ['bad'],
        ]));
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
     * test that modifying the whitelist works.
     */
    public function testDeprecatedMergeOptionsExtraWhitelist(): void
    {
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'page' => 10,
            'limit' => 10,
            'fields' => ['bad.stuff'],
            'recursive' => 1000,
            'conditions' => ['bad.stuff'],
            'contain' => ['bad'],
        ]));
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $this->deprecated(function () use ($settings): void {
            $this->Paginator->setConfig('whitelist', ['fields']);
            $result = $this->Paginator->mergeOptions('Post', $settings);
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
     * Test path for deprecated whitelist.
     */
    public function testDeprecatedPathMergeOptionsExtraWhitelist(): void
    {
        $this->expectDeprecation();
        $this->expectDeprecationMessage('Paginator.php');
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'page' => 10,
            'limit' => 10,
            'fields' => ['bad.stuff'],
            'recursive' => 1000,
            'conditions' => ['bad.stuff'],
            'contain' => ['bad'],
        ]));
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];

        $this->Paginator->setConfig('whitelist', ['fields']);
        $result = $this->Paginator->mergeOptions('Post', $settings);
        $expected = [
            'page' => 10,
            'limit' => 10,
            'maxLimit' => 100,
            'fields' => ['bad.stuff'],
            'whitelist' => ['limit', 'sort', 'page', 'direction', 'fields'],
            'allowedParameters' => ['limit', 'sort', 'page', 'direction', 'fields'],
        ];
    }

    /**
     * test mergeOptions with limit > maxLimit in code.
     */
    public function testMergeOptionsMaxLimit(): void
    {
        $settings = [
            'limit' => 200,
            'paramType' => 'named',
        ];
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
     */
    public function testGetDefaultMaxLimit(): void
    {
        $settings = [
            'page' => 1,
            'limit' => 2,
            'maxLimit' => 10,
            'order' => [
                'Users.username' => 'asc',
            ],
        ];
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
     */
    public function testValidateSortInvalid(): void
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

        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'page' => 1,
            'sort' => 'id',
            'direction' => 'herp',
        ]));
        $this->Paginator->paginate($table);
        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame('id', $params['PaginatorPosts']['sort']);
        $this->assertSame('asc', $params['PaginatorPosts']['direction']);
    }

    /**
     * test that invalid directions are ignored.
     */
    public function testValidateSortInvalidDirection(): void
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

        $this->assertSame('asc', $result['order']['model.something']);
    }

    /**
     * Test empty pagination result.
     */
    public function testEmptyPaginationResult(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $table->deleteAll('1=1');

        $this->Paginator->paginate($table);

        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(
            0,
            $params['PaginatorPosts']['count'],
            'Count should be 0'
        );
        $this->assertSame(
            1,
            $params['PaginatorPosts']['page'],
            'Page number should not be 0'
        );
        $this->assertSame(
            1,
            $params['PaginatorPosts']['pageCount'],
            'Page count number should not be 0'
        );
    }

    /**
     * Test that a really large page number gets clamped to the max page size.
     */
    public function testOutOfRangePageNumberGetsClamped(): void
    {
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams(['page' => 3000]));

        $table = $this->getTableLocator()->get('PaginatorPosts');

        $e = null;
        try {
            $this->Paginator->paginate($table);
        } catch (NotFoundException $e) {
        }

        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(
            1,
            $params['PaginatorPosts']['page'],
            'Page number should not be 0'
        );

        $this->assertNotNull($e);
        $this->assertInstanceOf(PageOutOfBoundsException::class, $e->getPrevious());
    }

    /**
     * Test that a out of bounds request still knows about the page size
     */
    public function testOutOfRangePageNumberStillProvidesPageCount(): void
    {
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'limit' => 1,
            'page' => '4',
        ]));

        $table = $this->getTableLocator()->get('PaginatorPosts');

        $e = null;
        try {
            $this->Paginator->paginate($table);
        } catch (NotFoundException $e) {
        }

        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(
            3,
            $params['PaginatorPosts']['pageCount'],
            'Page count number should not be 0'
        );

        $this->assertNotNull($e);
        $this->assertInstanceOf(PageOutOfBoundsException::class, $e->getPrevious());
    }

    /**
     * Test that a really REALLY large page number gets clamped to the max page size.
     */
    public function testOutOfVeryBigPageNumberGetsClamped(): void
    {
        $this->expectException(NotFoundException::class);
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'page' => '3000000000000000000000000',
        ]));

        $table = $this->getTableLocator()->get('PaginatorPosts');
        $this->Paginator->paginate($table);
    }

    /**
     * test that fields not in sortableFields won't be part of order conditions.
     */
    public function testValidateAllowedSortFailure(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')->will($this->returnValue(true));

        $options = [
            'sort' => 'body',
            'direction' => 'asc',
            'sortableFields' => ['title', 'id'],
        ];
        $result = $this->Paginator->validateSort($model, $options);

        $this->assertEquals([], $result['order']);
    }

    /**
     * test that fields in the sortableFields are not validated
     */
    public function testValidateAllowedSortTrusted(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->once())
            ->method('hasField')
            ->will($this->returnValue(true));

        $options = [
            'sort' => 'body',
            'direction' => 'asc',
            'sortableFields' => ['body'],
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
     */
    public function testValidateAllowedSortEmpty(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')
            ->will($this->returnValue(true));

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
     */
    public function testValidateSortNotInSchema(): void
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
     */
    public function testValidateSortAllowMultiple(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->once())
            ->method('hasField')
            ->will($this->returnValue(true));

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
     * test that multiple sort works.
     */
    public function testValidateSortMultiple(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')->will($this->returnValue(true));

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
     * Tests that order strings can used by Paginator
     */
    public function testValidateSortWithString(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')->will($this->returnValue(true));

        $options = [
            'order' => 'model.author_id DESC',
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $expected = 'model.author_id DESC';

        $this->assertSame($expected, $result['order']);
    }

    /**
     * Test that no sort doesn't trigger an error.
     */
    public function testValidateSortNoSort(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')
            ->will($this->returnValue(true));

        $options = [
            'direction' => 'asc',
            'sortableFields' => ['title', 'id'],
        ];
        $result = $this->Paginator->validateSort($model, $options);
        $this->assertEquals([], $result['order']);
    }

    /**
     * Test sorting with incorrect aliases on valid fields.
     */
    public function testValidateSortInvalidAlias(): void
    {
        $model = $this->getMockRepository();
        $model->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('model'));
        $model->expects($this->any())->method('hasField')->will($this->returnValue(true));

        $options = ['sort' => 'Derp.id'];
        $result = $this->Paginator->validateSort($model, $options);
        $this->assertEquals([], $result['order']);
    }

    /**
     * @return array
     */
    public function checkLimitProvider(): array
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
     */
    public function testCheckLimit(array $input, int $expected): void
    {
        $result = $this->Paginator->checkLimit($input);
        $this->assertSame($expected, $result['limit']);
    }

    /**
     * Integration test for checkLimit() being applied inside paginate()
     */
    public function testPaginateMaxLimit(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');

        $settings = [
            'maxLimit' => 100,
        ];
        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'limit' => '1000',
        ]));
        $this->Paginator->paginate($table, $settings);
        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(100, $params['PaginatorPosts']['limit']);
        $this->assertSame(100, $params['PaginatorPosts']['perPage']);

        $this->controller->setRequest($this->controller->getRequest()->withQueryParams([
            'limit' => '10',
        ]));
        $this->Paginator->paginate($table, $settings);
        $params = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(10, $params['PaginatorPosts']['limit']);
        $this->assertSame(10, $params['PaginatorPosts']['perPage']);
    }

    /**
     * test paginate() and custom find, to make sure the correct count is returned.
     */
    public function testPaginateCustomFind(): void
    {
        $titleExtractor = function ($result) {
            $ids = [];
            foreach ($result as $record) {
                $ids[] = $record->title;
            }

            return $ids;
        };

        $table = $this->getTableLocator()->get('PaginatorPosts');
        $data = ['author_id' => 3, 'title' => 'Fourth Post', 'body' => 'Article Body, unpublished', 'published' => 'N'];
        $result = $table->save(new Entity($data));
        $this->assertNotEmpty($result);

        $result = $this->Paginator->paginate($table);
        $this->assertCount(4, $result, '4 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post', 'Fourth Post'], $titleExtractor($result));

        $result = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(4, $result['PaginatorPosts']['current']);
        $this->assertSame(4, $result['PaginatorPosts']['count']);

        $settings = ['finder' => 'published'];
        $result = $this->Paginator->paginate($table, $settings);
        $this->assertCount(3, $result, '3 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post'], $titleExtractor($result));

        $result = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(3, $result['PaginatorPosts']['current']);
        $this->assertSame(3, $result['PaginatorPosts']['count']);

        $settings = ['finder' => 'published', 'limit' => 2, 'page' => 2];
        $result = $this->Paginator->paginate($table, $settings);
        $this->assertCount(1, $result, '1 rows should come back');
        $this->assertEquals(['Third Post'], $titleExtractor($result));

        $result = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(1, $result['PaginatorPosts']['current']);
        $this->assertSame(3, $result['PaginatorPosts']['count']);
        $this->assertSame(2, $result['PaginatorPosts']['pageCount']);

        $settings = ['finder' => 'published', 'limit' => 2];
        $result = $this->Paginator->paginate($table, $settings);
        $this->assertCount(2, $result, '2 rows should come back');
        $this->assertEquals(['First Post', 'Second Post'], $titleExtractor($result));

        $result = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(2, $result['PaginatorPosts']['current']);
        $this->assertSame(3, $result['PaginatorPosts']['count']);
        $this->assertSame(2, $result['PaginatorPosts']['pageCount']);
        $this->assertTrue($result['PaginatorPosts']['nextPage']);
        $this->assertFalse($result['PaginatorPosts']['prevPage']);
        $this->assertSame(2, $result['PaginatorPosts']['perPage']);
        $this->assertNull($result['PaginatorPosts']['limit']);
    }

    /**
     * test paginate() and custom find with fields array, to make sure the correct count is returned.
     */
    public function testPaginateCustomFindFieldsArray(): void
    {
        $table = $this->getTableLocator()->get('PaginatorPosts');
        $data = ['author_id' => 3, 'title' => 'Fourth Article', 'body' => 'Article Body, unpublished', 'published' => 'N'];
        $table->save(new Entity($data));

        $settings = [
            'finder' => 'list',
            'conditions' => ['PaginatorPosts.published' => 'Y'],
            'limit' => 2,
        ];
        $results = $this->Paginator->paginate($table, $settings);

        $result = $results->toArray();
        $expected = [
            1 => 'First Post',
            2 => 'Second Post',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->controller->getRequest()->getAttribute('paging');
        $this->assertSame(2, $result['PaginatorPosts']['current']);
        $this->assertSame(3, $result['PaginatorPosts']['count']);
        $this->assertSame(2, $result['PaginatorPosts']['pageCount']);
        $this->assertTrue($result['PaginatorPosts']['nextPage']);
        $this->assertFalse($result['PaginatorPosts']['prevPage']);
    }

    /**
     * test paginate() and custom finders to ensure the count + find
     * use the custom type.
     */
    public function testPaginateCustomFindCount(): void
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
        $this->Paginator->paginate($table, $settings);
    }

    /**
     * Tests that it is possible to pass an already made query object to
     * paginate()
     */
    public function testPaginateQuery(): void
    {
        $this->controller->setRequest(
            $this->controller->getRequest()->withQueryParams(['page' => '-1'])
        );
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
        $this->Paginator->paginate($query, $settings);
    }

    /**
     * test paginate() with bind()
     */
    public function testPaginateQueryWithBindValue(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(strpos($config['driver'], 'Sqlserver') !== false, 'Test temporarily broken in SQLServer');

        $table = $this->getTableLocator()->get('PaginatorPosts');
        $query = $table->find()
            ->where(['PaginatorPosts.author_id BETWEEN :start AND :end'])
            ->bind(':start', 1)
            ->bind(':end', 2);

        $results = $this->Paginator->paginate($query, []);

        $result = $results->toArray();
        $this->assertCount(2, $result);
        $this->assertSame('First Post', $result[0]->title);
        $this->assertSame('Third Post', $result[1]->title);
    }

    /**
     * Tests that passing a query object with a limit clause set will
     * overwrite it with the passed defaults.
     */
    public function testPaginateQueryWithLimit(): void
    {
        $this->controller->setRequest(
            $this->controller->getRequest()->withQueryParams(['page' => '-1'])
        );
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
        $this->Paginator->paginate($query, $settings);
    }

    /**
     * Helper method for making mocks.
     *
     * @param array $methods
     * @return \Cake\ORM\Table|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function _getMockPosts(array $methods = [])
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
     * @return \Cake\ORM\Query|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function _getMockFindQuery(?RepositoryInterface $table = null)
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

        $query->expects($this->any())
            ->method('count')
            ->will($this->returnValue(2));

        if ($table) {
            $query->repository($table);
        }

        return $query;
    }

    /**
     * @return \Cake\Datasource\RepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockRepository()
    {
        $model = $this->getMockBuilder(RepositoryInterface::class)
            ->onlyMethods([
                'getAlias', 'setAlias', 'setRegistryAlias', 'getRegistryAlias', 'hasField',
                'find', 'get', 'query', 'updateAll', 'deleteAll', 'newEmptyEntity',
                'exists', 'save', 'delete', 'newEntity', 'newEntities', 'patchEntity', 'patchEntities',
            ])
            ->getMock();

        return $model;
    }
}
