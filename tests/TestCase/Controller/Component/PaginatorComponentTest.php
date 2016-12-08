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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\PaginatorComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * PaginatorTestController class
 */
class PaginatorTestController extends Controller
{

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Paginator'];
}

class PaginatorComponentTest extends TestCase
{

    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.posts'];

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

        $this->request = new Request('controller_posts/index');
        $this->request->params['pass'] = [];
        $controller = new Controller($this->request);
        $registry = new ComponentRegistry($controller);
        $this->Paginator = new PaginatorComponent($registry, []);

        $this->Post = $this->getMockBuilder('Cake\ORM\Table')
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

        $this->request->query = ['page' => '1 " onclick="alert(\'xss\');">'];
        $settings = ['limit' => 1, 'maxLimit' => 10];
        $this->Paginator->paginate($this->Post, $settings);
        $this->assertSame(1, $this->request->params['paging']['Posts']['page'], 'XSS exploit opened');
    }

    /**
     * test that unknown keys in the default settings are
     * passed to the find operations.
     *
     * @return void
     */
    public function testPaginateExtraParams()
    {
        $this->request->query = ['page' => '-1'];
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
        $this->Paginator->paginate($table, $settings);
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
        $result = $this->Paginator->paginate($table, $settings)->count();

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

        $this->Paginator->paginate($table, $settings);
        $this->assertEquals('popular', $this->request->params['paging']['PaginatorPosts']['finder']);
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

        $this->Paginator->paginate($table, $settings);
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

        $this->Paginator->paginate($table, $settings);
        $this->assertEquals('PaginatorPosts.id', $this->request->params['paging']['PaginatorPosts']['sortDefault']);
        $this->assertEquals('DESC', $this->request->params['paging']['PaginatorPosts']['directionDefault']);
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
        $result = $this->Paginator->mergeOptions('Silly', $settings);
        $this->assertEquals($settings, $result);

        $result = $this->Paginator->mergeOptions('Posts', $settings);
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
        $this->request->query = [
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $this->request->query = [
            'page' => 10,
            'limit' => 10
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
            'finder' => 'myCustomFind'
        ];
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $this->request->query = [
            'page' => 99,
            'limit' => 75
        ];
        $settings = [
            'page' => 1,
            'limit' => 20,
            'maxLimit' => 100,
        ];
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $this->request->query = [
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $this->request->query = [
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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
        $result = $this->Paginator->mergeOptions('Post', $settings);
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

        $this->request->query = [
            'page' => 1,
            'sort' => 'id',
            'direction' => 'herp'
        ];
        $this->Paginator->paginate($table);
        $this->assertEquals('PaginatorPosts.id', $this->request->params['paging']['PaginatorPosts']['sort']);
        $this->assertEquals('asc', $this->request->params['paging']['PaginatorPosts']['direction']);
    }

    /**
     * test that invalid directions are ignored.
     *
     * @return void
     */
    public function testValidateSortInvalidDirection()
    {
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $this->request->query['page'] = 3000;

        $table = TableRegistry::get('PaginatorPosts');
        try {
            $this->Paginator->paginate($table);
            $this->fail('No exception raised');
        } catch (NotFoundException $e) {
            $this->assertEquals(
                1,
                $this->request->params['paging']['PaginatorPosts']['page'],
                'Page number should not be 0'
            );
        }
    }

    /**
     * Test that a really REALLY large page number gets clamped to the max page size.
     *
     * @expectedException \Cake\Network\Exception\NotFoundException
     * @return void
     */
    public function testOutOfVeryBigPageNumberGetsClamped()
    {
        $this->loadFixtures('Posts');
        $this->request->query = [
            'page' => '3000000000000000000000000',
        ];

        $table = TableRegistry::get('PaginatorPosts');
        $this->Paginator->paginate($table);
    }

    /**
     * test that fields not in whitelist won't be part of order conditions.
     *
     * @return void
     */
    public function testValidateSortWhitelistFailure()
    {
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $model = $this->getMockBuilder('Cake\ORM\Table')->getMock();
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
        $this->request->query = [
            'limit' => '1000'
        ];
        $this->Paginator->paginate($table, $settings);
        $this->assertEquals(100, $this->request->params['paging']['PaginatorPosts']['limit']);
        $this->assertEquals(100, $this->request->params['paging']['PaginatorPosts']['perPage']);

        $this->request->query = [
            'limit' => '10'
        ];
        $this->Paginator->paginate($table, $settings);
        $this->assertEquals(10, $this->request->params['paging']['PaginatorPosts']['limit']);
        $this->assertEquals(10, $this->request->params['paging']['PaginatorPosts']['perPage']);
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

        $result = $this->request->params['paging']['PaginatorPosts'];
        $this->assertEquals(4, $result['current']);
        $this->assertEquals(4, $result['count']);

        $settings = ['finder' => 'published'];
        $result = $this->Paginator->paginate($table, $settings);
        $this->assertCount(3, $result, '3 rows should come back');
        $this->assertEquals(['First Post', 'Second Post', 'Third Post'], $titleExtractor($result));

        $result = $this->request->params['paging']['PaginatorPosts'];
        $this->assertEquals(3, $result['current']);
        $this->assertEquals(3, $result['count']);

        $settings = ['finder' => 'published', 'limit' => 2, 'page' => 2];
        $result = $this->Paginator->paginate($table, $settings);
        $this->assertCount(1, $result, '1 rows should come back');
        $this->assertEquals(['Third Post'], $titleExtractor($result));

        $result = $this->request->params['paging']['PaginatorPosts'];
        $this->assertEquals(1, $result['current']);
        $this->assertEquals(3, $result['count']);
        $this->assertEquals(2, $result['pageCount']);

        $settings = ['finder' => 'published', 'limit' => 2];
        $result = $this->Paginator->paginate($table, $settings);
        $this->assertCount(2, $result, '2 rows should come back');
        $this->assertEquals(['First Post', 'Second Post'], $titleExtractor($result));

        $result = $this->request->params['paging']['PaginatorPosts'];
        $this->assertEquals(2, $result['current']);
        $this->assertEquals(3, $result['count']);
        $this->assertEquals(2, $result['pageCount']);
        $this->assertTrue($result['nextPage']);
        $this->assertFalse($result['prevPage']);
        $this->assertEquals(2, $result['perPage']);
        $this->assertNull($result['limit']);
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
        $results = $this->Paginator->paginate($table, $settings);

        $result = $results->toArray();
        $expected = [
            1 => 'First Post',
            2 => 'Second Post',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->request->params['paging']['PaginatorPosts'];
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
        $this->Paginator->paginate($table, $settings);
    }

    /**
     * Tests that it is possible to pass an already made query object to
     * paginate()
     *
     * @return void
     */
    public function testPaginateQuery()
    {
        $this->request->query = ['page' => '-1'];
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
        $this->Paginator->paginate($query, $settings);
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
        $this->request->query = ['page' => '-1'];
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
        $this->Paginator->paginate($query, $settings);
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
