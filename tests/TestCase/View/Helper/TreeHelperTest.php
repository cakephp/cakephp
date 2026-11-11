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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Chronos\Chronos;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Cookie\SameSiteEnum;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Cake\View\Helper\TreeHelper;
use Cake\View\View;
use RuntimeException;
use TestApp\Model\Enum\Priority;
use function Cake\Core\h;

class TreeHelperTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected array $fixtures = [
        'core.NumberTrees',
    ];

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $table;

    /**
     * @var \Cake\View\Helper\TreeHelper
     */
    protected TreeHelper $tree;

    /**
     * Initial Tree
     *
     * - One
     * -- One-SubA
     * - Two
     * -- Two-SubA
     * --- Two-SubA-1
     * ---- Two-SubA-1-1
     * - Three
     * - Four
     * -- Four-SubA
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->tree = new TreeHelper(new View());
        $this->table = $this->getTableLocator()->get('NumberTrees');
        $this->table->addBehavior('Tree');

        $connection = ConnectionManager::get('test');
        $sql = $this->table->getSchema()->truncateSql($connection);
        foreach ($sql as $snippet) {
            $connection->execute($snippet);
        }

        $data = [
            ['name' => 'One'],
            ['name' => 'Two'],
            ['name' => 'Three'],
            ['name' => 'Four'],

            ['name' => 'One-SubA', 'parent_id' => 1],
            ['name' => 'Two-SubA', 'parent_id' => 2],
            ['name' => 'Four-SubA', 'parent_id' => 4],

            ['name' => 'Two-SubA-1', 'parent_id' => 6],

            ['name' => 'Two-SubA-1-1', 'parent_id' => 8],
        ];
        foreach ($data as $row) {
            $row = new Entity($row);
            $this->table->save($row);
        }
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->table);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testGenerate()
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree);

        $expected = <<<TEXT

<ul>
    <li>One
    <ul>
        <li>One-SubA</li>
    </ul>
    </li>
    <li>Two
    <ul>
        <li>Two-SubA
        <ul>
            <li>Two-SubA-1
            <ul>
                <li>Two-SubA-1-1</li>
            </ul>
            </li>
        </ul>
        </li>
    </ul>
    </li>
    <li>Three</li>
    <li>Four
    <ul>
        <li>Four-SubA</li>
    </ul>
    </li>
</ul>

TEXT;
        $this->assertTextEquals($expected, $output);
        $this->assertTrue(substr_count($output, '<ul>') === substr_count($output, '</ul>'));
        $this->assertTrue(substr_count($output, '<li>') === substr_count($output, '</li>'));
    }

    /**
     * @return void
     */
    public function testGenerateWithFindAll()
    {
        $tree = $this->table->find('all', ...['order' => ['lft' => 'ASC']])->toArray();

        $output = $this->tree->generate($tree);
        $expected = <<<TEXT

<ul>
    <li>One
    <ul>
        <li>One-SubA</li>
    </ul>
    </li>
    <li>Two
    <ul>
        <li>Two-SubA
        <ul>
            <li>Two-SubA-1
            <ul>
                <li>Two-SubA-1-1</li>
            </ul>
            </li>
        </ul>
        </li>
    </ul>
    </li>
    <li>Three</li>
    <li>Four
    <ul>
        <li>Four-SubA</li>
    </ul>
    </li>
</ul>

TEXT;
        $output = str_replace([' ', "\r", "\n"], '', $output);
        $expected = str_replace([' ', "\r", "\n"], '', $expected);
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateWithDepth()
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree, ['depth' => 1]);
        $expected = <<<TEXT

    <ul>
        <li>One
        <ul>
            <li>One-SubA</li>
        </ul>
        </li>
        <li>Two
        <ul>
            <li>Two-SubA
            <ul>
                <li>Two-SubA-1
                <ul>
                    <li>Two-SubA-1-1</li>
                </ul>
                </li>
            </ul>
            </li>
        </ul>
        </li>
        <li>Three</li>
        <li>Four
        <ul>
            <li>Four-SubA</li>
        </ul>
        </li>
    </ul>

TEXT;
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateWithSettings()
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree, ['class' => 'navi', 'id' => 'main', 'type' => 'ol']);
        $expected = <<<TEXT

<ol class="navi" id="main">
    <li>One
    <ol>
        <li>One-SubA</li>
    </ol>
    </li>
    <li>Two
    <ol>
        <li>Two-SubA
        <ol>
            <li>Two-SubA-1
            <ol>
                <li>Two-SubA-1-1</li>
            </ol>
            </li>
        </ol>
        </li>
    </ol>
    </li>
    <li>Three</li>
    <li>Four
    <ol>
        <li>Four-SubA</li>
    </ol>
    </li>
</ol>

TEXT;
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateWithSameTypeAndItemType()
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree, ['type' => 'div', 'itemType' => 'div']);
        $expected = <<<TEXT

<div>
    <div>One
    <div>
        <div>One-SubA</div>
    </div>
    </div>
    <div>Two
    <div>
        <div>Two-SubA
        <div>
            <div>Two-SubA-1
            <div>
                <div>Two-SubA-1-1</div>
            </div>
            </div>
        </div>
        </div>
    </div>
    </div>
    <div>Three</div>
    <div>Four
    <div>
        <div>Four-SubA</div>
    </div>
    </div>
</div>

TEXT;
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateWithMaxDepth()
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree, ['maxDepth' => 2]);
        $expected = <<<TEXT

<ul>
    <li>One
    <ul>
        <li>One-SubA</li>
    </ul>
    </li>
    <li>Two
    <ul>
        <li>Two-SubA
        <ul>
            <li>Two-SubA-1</li>
        </ul>
        </li>
    </ul>
    </li>
    <li>Three</li>
    <li>Four
    <ul>
        <li>Four-SubA</li>
    </ul>
    </li>
</ul>

TEXT;
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateWithAutoPath()
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree, ['autoPath' => [7, 10]]); // Two-SubA-1
        $expected = <<<TEXT

<ul>
    <li>One
    <ul>
        <li>One-SubA</li>
    </ul>
    </li>
    <li class="active">Two
    <ul>
        <li class="active">Two-SubA
        <ul>
            <li class="active">Two-SubA-1
            <ul>
                <li>Two-SubA-1-1</li>
            </ul>
            </li>
        </ul>
        </li>
    </ul>
    </li>
    <li>Three</li>
    <li>Four
    <ul>
        <li>Four-SubA</li>
    </ul>
    </li>
</ul>

TEXT;
        $this->assertTextEquals($expected, $output);

        $output = $this->tree->generate($tree, ['autoPath' => [8, 9]]); // Two-SubA-1-1
        $expected = <<<TEXT

<ul>
    <li>One
    <ul>
        <li>One-SubA</li>
    </ul>
    </li>
    <li class="active">Two
    <ul>
        <li class="active">Two-SubA
        <ul>
            <li class="active">Two-SubA-1
            <ul>
                <li class="active">Two-SubA-1-1</li>
            </ul>
            </li>
        </ul>
        </li>
    </ul>
    </li>
    <li>Three</li>
    <li>Four
    <ul>
        <li>Four-SubA</li>
    </ul>
    </li>
</ul>

TEXT;
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateWithAutoPathAsEntity()
    {
        $tree = $this->table->find('threaded')->toArray();
        $entity = new Entity();
        $entity->lft = 7;
        $entity->rght = 10;

        $output = $this->tree->generate($tree, ['autoPath' => $entity]);
        $expected = <<<TEXT

<ul>
    <li>One
    <ul>
        <li>One-SubA</li>
    </ul>
    </li>
    <li class="active">Two
    <ul>
        <li class="active">Two-SubA
        <ul>
            <li class="active">Two-SubA-1
            <ul>
                <li>Two-SubA-1-1</li>
            </ul>
            </li>
        </ul>
        </li>
    </ul>
    </li>
    <li>Three</li>
    <li>Four
    <ul>
        <li>Four-SubA</li>
    </ul>
    </li>
</ul>

TEXT;
        $this->assertTextEquals($expected, $output);
    }

    /**
     * - One
     * -- One-SubA
     * - Two
     * -- Two-SubA
     * --- Two-SubA-1
     * ---- Two-SubA-1-1
     * -- Two-SubB
     * -- Two-SubC
     * - Three
     * - Four
     * -- Four-SubA
     *
     * @return void
     */
    public function testGenerateWithAutoPathAndHideUnrelated()
    {
        $data = [
            ['name' => 'Two-SubB', 'parent_id' => 2],
            ['name' => 'Two-SubC', 'parent_id' => 2],
        ];
        foreach ($data as $row) {
            $row = new Entity($row);
            $this->table->save($row);
        }

        $tree = $this->table->find('threaded')->toArray();
        $id = 6;
        $nodes = $this->table->find('path', ...['for' => $id])->toArray();
        $path = Hash::extract($nodes, '{n}.id');

        $output = $this->tree->generate($tree, ['autoPath' => [6, 11], 'hideUnrelated' => true, 'treePath' => $path, 'callback' => $this->myCallback(...)]); // Two-SubA

        $expected = <<<TEXT

<ul>
    <li>One</li>
    <li class="active">Two (active)
    <ul>
        <li class="active">Two-SubA (active)
        <ul>
            <li>Two-SubA-1</li>
        </ul>
        </li>
        <li>Two-SubB</li>
        <li>Two-SubC</li>
    </ul>
    </li>
    <li>Three</li>
    <li>Four</li>
</ul>

TEXT;
        $output = str_replace([' '], '', $output);
        $expected = str_replace([' '], '', $expected);
        $this->assertTextEquals($expected, $output);
    }

    /**
     * - One
     * -- One-SubA
     * - Two
     * -- Two-SubA
     * --- Two-SubA-1
     * ---- Two-SubA-1-1
     * -- Two-SubB
     * -- Two-SubC
     * - Three
     * - Four
     * -- Four-SubA
     *
     * @return void
     */
    public function testGenerateWithAutoPathAndHideUnrelatedAndSiblings()
    {
        $data = [
            ['name' => 'Two-SubB', 'parent_id' => 2],
            ['name' => 'Two-SubC', 'parent_id' => 2],
        ];
        foreach ($data as $row) {
            $row = new Entity($row);
            $this->table->save($row);
        }

        $tree = $this->table->find('threaded')->toArray();

        $id = 6; // Two-SubA
        $nodes = $this->table->find('path', ...['for' => $id])->toArray();
        $path = Hash::extract($nodes, '{n}.id');

        $output = $this->tree->generate($tree, [
            'autoPath' => [6, 11],
            'hideUnrelated' => true,
            'treePath' => $path,
            'callback' => $this->myCallbackSiblings(...),
        ]);

        $expected = <<<TEXT

<ul>
    <li>One (sibling)</li>
    <li class="active">Two (active)
    <ul>
        <li class="active">Two-SubA (active)
        <ul>
            <li>Two-SubA-1</li>
        </ul>
        </li>
        <li>Two-SubB</li>
        <li>Two-SubC</li>
    </ul>
    </li>
    <li>Three (sibling)</li>
    <li>Four (sibling)</li>
</ul>

TEXT;
        $output = str_replace([' ', "\r", "\n"], '', $output);
        $expected = str_replace([' ', "\r", "\n"], '', $expected);
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @param array $data
     * @return string|null
     */
    protected function myCallback(array $data)
    {
        /** @var \Cake\ORM\Entity $entity */
        $entity = $data['data'];
        if (!empty($entity['hide'])) {
            return null;
        }

        return $data['data']['name'] . ($data['activePathElement'] ? ' (active)' : '');
    }

    /**
     * @param array $data
     * @return string|null
     */
    protected function myCallbackSiblings(array $data)
    {
        /** @var \Cake\ORM\Entity $entity */
        $entity = $data['data'];

        if (!empty($entity['hide'])) {
            return null;
        }
        if ($data['depth'] == 0 && $data['isSibling']) {
            return $entity['name'] . ' (sibling)';
        }

        return $entity['name'] . ($data['activePathElement'] ? ' (active)' : '');
    }

    /**
     * @return void
     */
    public function testGenerateProductive()
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree, ['indent' => false]);
        $expected = '<ul><li>One<ul><li>One-SubA</li></ul></li><li>Two<ul><li>Two-SubA<ul><li>Two-SubA-1<ul><li>Two-SubA-1-1</li></ul></li></ul></li></ul></li><li>Three</li><li>Four<ul><li>Four-SubA</li></ul></li></ul>';

        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateWithEntityUsage()
    {
        $data = [
            ['name' => 'Two-SubB', 'parent_id' => 2],
            ['name' => 'Two-SubC', 'parent_id' => 2],
        ];
        foreach ($data as $row) {
            $row = new Entity($row);
            $this->table->save($row);
        }

        $tree = $this->table->find('threaded')->toArray();

        $id = 6;
        $nodes = $this->table->find('path', ...['for' => $id])->toArray();
        $path = Hash::extract($nodes, '{n}.id');

        $output = $this->tree->generate($tree, [
            'autoPath' => [6, 11],
            'treePath' => $path,
            'callback' => $this->myCallbackEntity(...),
        ]); // Two-SubA

        $expected = <<<TEXT

<ul>
    <li>One
    <ul>
        <li>One-SubA</li>
    </ul>
    </li>
    <li class="active">Two (active)
    <ul>
        <li class="active">Two-SubA (active)
        <ul>
            <li>Two-SubA-1
            <ul>
                <li>Two-SubA-1-1</li>
            </ul>
            </li>
        </ul>
        </li>
        <li>Two-SubB</li>
        <li>Two-SubC</li>
    </ul>
    </li>
    <li>Three</li>
    <li>Four
    <ul>
        <li>Four-SubA</li>
    </ul>
    </li>
</ul>

TEXT;
        $output = str_replace([' ', "\r", "\n"], '', $output);
        $expected = str_replace([' ', "\r", "\n"], '', $expected);
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateFromArrayWithChildren(): void
    {
        $treeData = [
            [
                'name' => 'Foo',
                'children' => [
                    [
                        'name' => 'Bar',
                        'children' => [],
                    ],
                    [
                        'name' => 'Baz',
                        'children' => [
                            [
                                'name' => 'Baz Child',
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $output = $this->tree->generate($treeData);
        $expected = <<<TEXT

<ul>
    <li>Foo
    <ul>
        <li>Bar</li>
        <li>Baz
        <ul>
            <li>Baz Child</li>
        </ul>
        </li>
    </ul>
    </li>
</ul>

TEXT;
        $output = str_replace([' ', "\r", "\n"], '', $output);
        $expected = str_replace([' ', "\r", "\n"], '', $expected);
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateFromArrayWithChildrenInvalid(): void
    {
        $treeData = [
            [
                'name' => 'Foo',
                'children' => [
                    [
                        'name' => 'Bar',
                        'children' => [],
                    ],
                    [
                        'name' => 'Baz',
                        'children' => [
                            [
                                'name' => 'Baz Child',
                                //'children' => [] // Missing element
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Tree Structure: Array');

        $this->tree->generate($treeData);
    }

    /**
     * @return void
     */
    public function testGenerateWithSpecialStringable()
    {
        $treeData = [
            [
                'name' => 'Foo',
                'children' => [
                    [
                        'name' => SameSiteEnum::STRICT,
                        'children' => [],
                    ],
                    [
                        'name' => Priority::High,
                        'children' => [],
                    ],
                    [
                        'name' => new Chronos('2023-10-01 11:12:13'),
                        'children' => [],
                    ],
                    [
                        'name' => function () {
                            return 'Foo Bar';
                        },
                        'children' => [],
                    ],
                ],
            ],
        ];

        $output = $this->tree->generate($treeData);
        $expected = <<<TEXT

<ul>
    <li>Foo
    <ul>
        <li>Strict</li>
        <li>Is High</li>
        <li>2023-10-01 11:12:13</li>
        <li>Foo Bar</li>
    </ul>
    </li>
</ul>

TEXT;
        $output = str_replace([' ', "\r", "\n"], '', $output);
        $expected = str_replace([' ', "\r", "\n"], '', $expected);
        $this->assertTextEquals($expected, $output);
    }

    /**
     * @param array $data
     * @return string|null
     */
    protected function myCallbackEntity(array $data): ?string
    {
        /** @var \Cake\ORM\Entity $entity */
        $entity = $data['data'];

        return h($entity->name) . ($data['activePathElement'] ? ' (active)' : '');
    }
}
