<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Datasource\Paging\NumericPaginator;
use Cake\Datasource\Paging\SortField;
use Cake\Datasource\Paging\SortFieldBuilder;
use Cake\Datasource\Paging\SortableFieldsBuilder;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * NumericPaginator SortField Integration Test Case
 */
class NumericPaginatorSortFieldTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'core.Articles',
        'core.Authors',
    ];

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $table;

    /**
     * @var \Cake\Datasource\Paging\NumericPaginator
     */
    protected NumericPaginator $paginator;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->table = $this->getTableLocator()->get('Articles');
        $this->paginator = new NumericPaginator();
    }

    /**
     * Test paginator with SortField objects for default ascending sort
     *
     * @return void
     */
    public function testPaginateWithSortFieldAscending(): void
    {
        $params = [
            'sort' => 'newest',
        ];

        $settings = [
            'sortableFields' => [
                'newest' => [
                    SortField::asc('title'),
                    SortField::desc('published'),
                ],
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        // When no direction specified, should use SortField defaults
        $expected = [
            'Articles.title' => 'asc',
            'Articles.published' => 'desc',
        ];

        $this->assertEquals('newest', $pagingParams['sort']);
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test paginator with SortField objects when direction is explicitly specified
     *
     * @return void
     */
    public function testPaginateWithSortFieldExplicitDirection(): void
    {
        $params = [
            'sort' => 'newest',
            'direction' => 'desc',
        ];

        $settings = [
            'sortableFields' => [
                'newest' => [
                    SortField::asc('title'),
                    SortField::desc('published'),
                ],
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        // When direction is explicitly specified, toggleable fields should use it
        $expected = [
            'Articles.title' => 'desc',
            'Articles.published' => 'desc',
        ];

        $this->assertEquals('newest', $pagingParams['sort']);
        $this->assertEquals('desc', $pagingParams['direction']);
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test paginator with locked SortField objects
     *
     * @return void
     */
    public function testPaginateWithLockedSortField(): void
    {
        $params = [
            'sort' => 'popular',
            'direction' => 'asc', // Try to override the locked direction
        ];

        $settings = [
            'sortableFields' => [
                'popular' => [
                    SortField::desc('published', locked: true),
                    SortField::asc('title'),
                ],
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        // Locked field should always use its locked direction
        $expected = [
            'Articles.published' => 'desc', // Locked, ignores requested 'asc'
            'Articles.title' => 'asc', // Toggleable, uses requested 'asc'
        ];

        $this->assertEquals('popular', $pagingParams['sort']);
        $this->assertEquals('desc', $pagingParams['direction']); // First field's direction is what gets reported
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test paginator with mixed SortField objects and strings for backward compatibility
     *
     * @return void
     */
    public function testPaginateWithMixedSortFieldAndStrings(): void
    {
        $params = [
            'sort' => 'mixed',
            'direction' => 'desc',
        ];

        $settings = [
            'sortableFields' => [
                'mixed' => [
                    SortField::desc('published'),
                    'author_id', // String field for BC
                    SortField::asc('title', locked: true),
                ],
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.published' => 'desc', // SortField with default desc
            'Articles.author_id' => 'desc', // String field uses requested direction
            'Articles.title' => 'asc', // Locked field ignores requested direction
        ];

        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test paginator with combined sort format and SortField
     *
     * @return void
     */
    public function testPaginateWithCombinedSortFormat(): void
    {
        // Test with combined format: newest-desc
        $params = [
            'sort' => 'newest-desc',
        ];

        $settings = [
            'sortableFields' => [
                'newest' => [
                    SortField::asc('title'),
                    SortField::desc('published'),
                ],
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        // Direction is explicitly specified as desc
        $expected = [
            'Articles.title' => 'desc',
            'Articles.published' => 'desc',
        ];

        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test paginator with SortField when no direction is provided in combined format
     *
     * @return void
     */
    public function testPaginateWithCombinedSortFormatNoDirection(): void
    {
        // Test with combined format but no direction: just "newest"
        $params = [
            'sort' => 'newest',
        ];

        $settings = [
            'sortableFields' => [
                'newest' => [
                    SortField::desc('published'),
                    SortField::asc('title'),
                ],
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        // Should use default directions from SortField
        $expected = [
            'Articles.published' => 'desc',
            'Articles.title' => 'asc',
        ];

        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test complex real-world scenario with multiple sort maps
     *
     * @return void
     */
    public function testComplexRealWorldScenario(): void
    {
        $settings = [
            'sortableFields' => [
                'relevance' => [
                    SortField::desc('published', locked: true),
                    SortField::desc('author_id'),
                ],
                'newest' => [
                    SortField::desc('published'),
                    SortField::asc('title'),
                ],
                'alphabetical' => [
                    SortField::asc('title'),
                ],
                'author' => [
                    'author_id',
                    SortField::asc('title'),
                ],
            ],
        ];

        // Test relevance sort (with locked field)
        $params = ['sort' => 'relevance', 'direction' => 'asc'];
        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.published' => 'desc', // Locked, ignores 'asc'
            'Articles.author_id' => 'asc', // Toggleable, uses 'asc'
        ];

        $this->assertEquals($expected, $pagingParams['completeSort']);

        // Test newest sort without explicit direction
        $params = ['sort' => 'newest'];
        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.published' => 'desc',
            'Articles.title' => 'asc',
        ];

        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test that invalid sort keys are handled correctly with SortField
     *
     * @return void
     */
    public function testInvalidSortKeyWithSortField(): void
    {
        $params = [
            'sort' => 'invalid_key',
            'direction' => 'asc',
        ];

        $settings = [
            'sortableFields' => [
                'newest' => [
                    SortField::desc('published'),
                ],
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);

        // Invalid sort key should result in no sorting
        $pagingParams = $result->pagingParams();
        $this->assertNull($pagingParams['sort']);
        $this->assertNull($pagingParams['direction']);
    }

    /**
     * Test paginator with SortFieldBuilder preset methods
     *
     * @return void
     */
    public function testPaginateWithFactoryPresets(): void
    {
        $params = [
            'sort' => 'newest',
        ];

        $settings = [
            'sortableFields' => [
                'newest' => SortFieldBuilder::create()
                    ->desc('published')
                    ->asc('title')
                    ->build(),
                'oldest' => SortFieldBuilder::create()
                    ->asc('published')
                    ->build(),
                'alphabetical' => SortFieldBuilder::create()
                    ->asc('title')
                    ->build(),
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.published' => 'desc',
            'Articles.title' => 'asc',
        ];

        $this->assertEquals('newest', $pagingParams['sort']);
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test paginator with SortFieldBuilder fluent interface
     *
     * @return void
     */
    public function testPaginateWithFactoryFluentInterface(): void
    {
        $params = [
            'sort' => 'custom',
            'direction' => 'asc',
        ];

        $settings = [
            'sortableFields' => [
                'custom' => SortFieldBuilder::create()
                    ->desc('published')
                    ->asc('author_id', locked: true)
                    ->asc('title')
                    ->build(),
            ],
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.published' => 'asc', // Toggleable, uses requested 'asc'
            'Articles.author_id' => 'asc', // Locked, ignores requested direction
            'Articles.title' => 'asc', // Toggleable, uses requested 'asc'
        ];

        $this->assertEquals('custom', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']);
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test paginator with complete sorts built using factory
     *
     * @return void
     */
    public function testPaginateWithFactoryBuildMap(): void
    {
        $sorts = [
            'newest' => SortFieldBuilder::create()->desc('published')->build(),
            'popular' => SortFieldBuilder::create()->desc('published', locked: true)->build(),
            'alphabetical' => SortFieldBuilder::create()->asc('title')->build(),
        ];

        $params = [
            'sort' => 'popular',
            'direction' => 'asc', // Try to override locked direction
        ];

        $settings = [
            'sortableFields' => $sorts,
        ];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        // Popular preset is locked to desc
        $expected = [
            'Articles.published' => 'desc',
        ];

        $this->assertEquals('popular', $pagingParams['sort']);
        $this->assertEquals('desc', $pagingParams['direction']); // Locked field's direction is reported
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test paginator with SortableFieldsBuilder
     *
     * @return void
     */
    public function testSortsFactory(): void
    {
        $factory = new SortableFieldsBuilder();
        $factory
            ->add(
                'newest',
                SortField::desc('published'),
                SortField::asc('title'),
            )
            ->add(
                'popular',
                SortField::desc('published', locked: true),
                SortField::desc('author_id'),
            )
            ->add(
                'alphabetical',
                SortField::asc('title'),
            );

        $settings = [
            'sortableFields' => $factory->build(),
        ];

        // Test newest sort
        $params = ['sort' => 'newest'];
        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.published' => 'desc',
            'Articles.title' => 'asc',
        ];

        $this->assertEquals('newest', $pagingParams['sort']);
        $this->assertEquals($expected, $pagingParams['completeSort']);

        // Test popular sort with locked field
        $params = ['sort' => 'popular', 'direction' => 'asc'];
        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.published' => 'desc', // Locked
            'Articles.author_id' => 'asc', // Toggleable
        ];

        $this->assertEquals('popular', $pagingParams['sort']);
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test combined sort format with factory
     *
     * @return void
     */
    public function testCombinedSortFormatWithFactory(): void
    {
        $settings = [
            'sortableFields' => [
                'custom' => SortFieldBuilder::create()
                    ->desc('published')
                    ->asc('title')
                    ->build(),
            ],
        ];

        // Test with combined format: custom-asc
        $params = ['sort' => 'custom-asc'];

        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        // Direction is explicitly specified as asc
        $expected = [
            'Articles.published' => 'asc',
            'Articles.title' => 'asc',
        ];

        $this->assertEquals('custom', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']);
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }

    /**
     * Test SortableFieldsBuilder shorthand where key is used as field
     *
     * @return void
     */
    public function testSortsFactoryShorthand(): void
    {
        $factory = new SortableFieldsBuilder();
        $factory
            ->add('title') // Shorthand - uses 'title' as field
            ->add('published') // Shorthand - uses 'published' as field
            ->add('author_id'); // Shorthand - uses 'author_id' as field

        $settings = [
            'sortableFields' => $factory->build(),
        ];

        // Test title sort
        $params = ['sort' => 'title', 'direction' => 'desc'];
        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.title' => 'desc',
        ];

        $this->assertEquals('title', $pagingParams['sort']);
        $this->assertEquals('desc', $pagingParams['direction']);
        $this->assertEquals($expected, $pagingParams['completeSort']);

        // Test published sort
        $params = ['sort' => 'published', 'direction' => 'asc'];
        $result = $this->paginator->paginate($this->table, $params, $settings);
        $pagingParams = $result->pagingParams();

        $expected = [
            'Articles.published' => 'asc',
        ];

        $this->assertEquals('published', $pagingParams['sort']);
        $this->assertEquals('asc', $pagingParams['direction']);
        $this->assertEquals($expected, $pagingParams['completeSort']);
    }
}
