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
use Cake\Http\Cookie\Enum\SameSite;
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
    public function testGenerate(): void
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
    public function testGenerateWithFindAll(): void
    {
        $tree = $this->table->find('all', order: ['lft' => 'ASC'])->toArray();

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
    public function testGenerateWithDepth(): void
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
    public function testGenerateWithSettings(): void
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
    public function testGenerateWithSameTypeAndItemType(): void
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
    public function testGenerateWithMaxDepth(): void
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
    public function testGenerateWithAutoPath(): void
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
    public function testGenerateWithAutoPathAsEntity(): void
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
    public function testGenerateWithAutoPathAndHideUnrelated(): void
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
        $nodes = $this->table->find('path', for: $id)->toArray();
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
    public function testGenerateWithAutoPathAndHideUnrelatedAndSiblings(): void
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
        $nodes = $this->table->find('path', for: $id)->toArray();
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
    protected function myCallback(array $data): ?string
    {
        /** @var \Cake\ORM\Entity $entity */
        $entity = $data['data'];

        return $entity['name'] . ($data['activePathElement'] ? ' (active)' : '');
    }

    /**
     * @param array $data
     * @return string|null
     */
    protected function myCallbackSiblings(array $data): ?string
    {
        /** @var \Cake\ORM\Entity $entity */
        $entity = $data['data'];

        if ($data['depth'] == 0 && $data['isSibling']) {
            return $entity['name'] . ' (sibling)';
        }

        return $entity['name'] . ($data['activePathElement'] ? ' (active)' : '');
    }

    /**
     * @return void
     */
    public function testGenerateProductive(): void
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree, ['indent' => false]);
        $expected = '<ul><li>One<ul><li>One-SubA</li></ul></li><li>Two<ul><li>Two-SubA<ul><li>Two-SubA-1<ul><li>Two-SubA-1-1</li></ul></li></ul></li></ul></li><li>Three</li><li>Four<ul><li>Four-SubA</li></ul></li></ul>';

        $this->assertTextEquals($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateWithEntityUsage(): void
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
        $nodes = $this->table->find('path', for: $id)->toArray();
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
    public function testGenerateWithSpecialStringable(): void
    {
        $treeData = [
            [
                'name' => 'Foo',
                'children' => [
                    [
                        'name' => SameSite::STRICT,
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
     * @return void
     */
    public function testGenerateEscapesNodeContent(): void
    {
        $treeData = [
            [
                'name' => '<script>alert("xss")</script>',
                'children' => [
                    [
                        'name' => "O'Reilly & Sons",
                        'children' => [],
                    ],
                ],
            ],
        ];

        $output = $this->tree->generate($treeData, ['indent' => false]);
        $expected = '<ul><li>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'
            . '<ul><li>O&#039;Reilly &amp; Sons</li></ul></li></ul>';

        $this->assertSame($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateEscapesAttributes(): void
    {
        $treeData = [
            [
                'name' => '<script>alert("attribute")</script>',
                'children' => [],
            ],
        ];

        $output = $this->tree->generate($treeData, [
            'indent' => false,
            'class' => '"><script>alert("class")</script>',
            'callback' => function (array $data): string {
                $this->tree->addItemAttribute('title', $data['data']['name']);

                return 'Foo';
            },
        ]);
        $expected = '<ul class="&quot;&gt;&lt;script&gt;alert(&quot;class&quot;)&lt;/script&gt;">'
            . '<li title="&lt;script&gt;alert(&quot;attribute&quot;)&lt;/script&gt;">Foo</li></ul>';

        $this->assertSame($expected, $output);
    }

    /**
     * A(1-6) > B(2-5) > C(3-4), D(7-8)
     *
     * @return array<int, array<string, mixed>>
     */
    protected function flatTree(): array
    {
        return [
            ['id' => 1, 'name' => 'A', 'lft' => 1, 'rght' => 6],
            ['id' => 2, 'name' => 'B', 'lft' => 2, 'rght' => 5],
            ['id' => 3, 'name' => 'C', 'lft' => 3, 'rght' => 4],
            ['id' => 4, 'name' => 'D', 'lft' => 7, 'rght' => 8],
        ];
    }

    /**
     * @return void
     */
    public function testGenerateFlatDepthAndMaxDepth(): void
    {
        $depths = [];
        $callback = function (array $data) use (&$depths): string {
            $depths[] = $data['depth'];

            return $data['data']['name'];
        };

        $output = $this->tree->generate($this->flatTree(), ['indent' => false, 'callback' => $callback]);
        $this->assertSame('<ul><li>A<ul><li>B<ul><li>C</li></ul></li></ul></li><li>D</li></ul>', $output);
        $this->assertSame([0, 1, 2, 0], $depths);

        $output = $this->tree->generate($this->flatTree(), ['indent' => false, 'maxDepth' => 1]);
        $this->assertSame('<ul><li>A<ul><li>B</li></ul></li><li>D</li></ul>', $output);

        $output = $this->tree->generate($this->flatTree(), ['indent' => false, 'maxDepth' => 0]);
        $this->assertSame('<ul><li>A</li><li>D</li></ul>', $output);
    }

    /**
     * @return void
     */
    public function testGenerateFlatWithLevelField(): void
    {
        $depths = [];
        $callback = function (array $data) use (&$depths): string {
            $depths[$data['data']['name']] = $data['depth'];

            return $data['data']['name'];
        };

        $levels = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 0];
        $nodes = array_map(fn(array $row) => $row + ['lvl' => $levels[$row['name']]], $this->flatTree());
        $config = ['indent' => false, 'level' => 'lvl', 'callback' => $callback];

        $output = $this->tree->generate($nodes, $config);
        $this->assertSame('<ul><li>A<ul><li>B<ul><li>C</li></ul></li></ul></li><li>D</li></ul>', $output);
        $this->assertSame(['A' => 0, 'B' => 1, 'C' => 2, 'D' => 0], $depths);

        // Depth is relative to the first row, as when rendering the descendants of a node
        $depths = [];
        $output = $this->tree->generate(array_slice($nodes, 1, 2), $config);
        $this->assertSame('<ul><li>B<ul><li>C</li></ul></li></ul>', $output);
        $this->assertSame(['B' => 0, 'C' => 1], $depths);

        $output = $this->tree->generate($nodes, ['maxDepth' => 1] + $config);
        $this->assertSame('<ul><li>A<ul><li>B</li></ul></li><li>D</li></ul>', $output);
    }

    /**
     * @return void
     */
    public function testGenerateFlatCallbackSkipsKeepMarkupBalanced(): void
    {
        $skip = fn(array $data): ?string => in_array($data['data']['name'], ['B', 'C'], true) ? null : $data['data']['name'];
        $output = $this->tree->generate($this->flatTree(), ['indent' => false, 'callback' => $skip]);
        $this->assertSame('<ul><li>A</li><li>D</li></ul>', $output);

        $skip = fn(array $data): ?string => $data['data']['name'] === 'C' ? null : $data['data']['name'];
        $output = $this->tree->generate($this->flatTree(), ['indent' => false, 'callback' => $skip]);
        $this->assertSame('<ul><li>A<ul><li>B</li></ul></li><li>D</li></ul>', $output);
    }

    /**
     * @return void
     */
    public function testGenerateSkippedItemDoesNotLeakAttributes(): void
    {
        $treeData = [
            ['name' => 'One', 'children' => []],
            ['name' => 'Two', 'children' => []],
        ];
        $callback = function (array $data): ?string {
            if ($data['data']['name'] === 'One') {
                $this->tree->addItemAttribute('class', 'leaked');

                return null;
            }

            return 'Two';
        };

        $output = $this->tree->generate($treeData, ['indent' => false, 'callback' => $callback]);
        $this->assertSame('<ul><li>Two</li></ul>', $output);
    }

    /**
     * @return void
     */
    public function testGenerateSplitCount(): void
    {
        $treeData = [];
        foreach (range(1, 5) as $i) {
            $treeData[] = ['name' => (string)$i, 'children' => []];
        }
        $expected = '<ul><li>1</li><li>2</li><li>3</li></ul><ul><li>4</li><li>5</li></ul>';

        $first = $this->tree->generate($treeData, ['indent' => false, 'splitCount' => 2]);
        $second = $this->tree->generate($treeData, ['indent' => false, 'splitCount' => 2]);

        $this->assertSame($expected, $first);
        $this->assertSame($first, $second);
    }

    /**
     * @return void
     */
    public function testGenerateSplitDepth(): void
    {
        $children = [];
        foreach (range(1, 5) as $i) {
            $children[] = ['name' => 'c' . $i, 'children' => []];
        }
        $treeData = [
            ['name' => 'One', 'children' => $children],
            ['name' => 'Two', 'children' => []],
        ];

        $output = $this->tree->generate($treeData, ['indent' => false, 'splitDepth' => 1, 'splitCount' => 2]);
        $expected = '<ul><li>One<ul><li>c1</li><li>c2</li><li>c3</li></ul><ul><li>c4</li><li>c5</li></ul></li>'
            . '<li>Two</li></ul>';
        $this->assertSame($expected, $output);

        $output = $this->tree->generate($treeData, ['indent' => false, 'splitDepth' => 1]);
        $this->assertStringNotContainsString('</ul><ul>', $output);
    }

    /**
     * @return void
     */
    public function testGenerateSplitCountClosesParentItemFirst(): void
    {
        $treeData = [
            ['name' => 'One', 'children' => [['name' => 'Sub', 'children' => []]]],
            ['name' => 'Two', 'children' => []],
        ];

        $output = $this->tree->generate($treeData, ['indent' => false, 'splitCount' => 2]);
        $expected = '<ul><li>One<ul><li>Sub</li></ul></li></ul><ul><li>Two</li></ul>';
        $this->assertSame($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateSplitCountFlat(): void
    {
        $treeData = $this->flatTree();
        $treeData[] = ['id' => 5, 'name' => 'E', 'lft' => 9, 'rght' => 10];

        $output = $this->tree->generate($treeData, ['indent' => false, 'splitCount' => 2]);
        $expected = '<ul><li>A<ul><li>B<ul><li>C</li></ul></li></ul></li><li>D</li></ul><ul><li>E</li></ul>';
        $this->assertSame($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateHideUnrelatedWithTreePathOnly(): void
    {
        $tree = $this->table->find('threaded')->toArray();

        $output = $this->tree->generate($tree, [
            'indent' => false,
            'hideUnrelated' => true,
            'treePath' => [2, 6],
        ]);
        $expected = '<ul><li>One</li><li>Two<ul><li>Two-SubA<ul><li>Two-SubA-1</li></ul></li></ul></li>'
            . '<li>Three</li><li>Four</li></ul>';
        $this->assertSame($expected, $output);
    }

    /**
     * @return void
     */
    public function testGenerateHideUnrelatedKeepsEntitiesIntact(): void
    {
        $tree = $this->table->find('threaded')->toArray();
        $seen = [];
        $callback = function (array $data) use (&$seen): string {
            $seen[] = $data['data']->get('name');

            return $data['data']->name;
        };

        $config = [
            'indent' => false,
            'hideUnrelated' => true,
            'treePath' => [2],
            'callback' => $callback,
        ];
        $first = $this->tree->generate($tree, $config);
        $second = $this->tree->generate($tree, $config);

        $this->assertSame($first, $second);
        $this->assertSame(['One', 'Two', 'Two-SubA', 'Three', 'Four'], array_slice($seen, 0, 5));
        $this->assertCount(1, $tree[1]->get('children'));
    }

    /**
     * @return void
     */
    public function testGenerateHideUnrelatedFlat(): void
    {
        $output = $this->tree->generate($this->flatTree(), [
            'indent' => false,
            'hideUnrelated' => true,
            'treePath' => [1, 2],
        ]);
        $this->assertSame('<ul><li>A<ul><li>B<ul><li>C</li></ul></li></ul></li><li>D</li></ul>', $output);

        $output = $this->tree->generate($this->flatTree(), [
            'indent' => false,
            'hideUnrelated' => true,
            'treePath' => [4],
        ]);
        $this->assertSame('<ul><li>A</li><li>D</li></ul>', $output);
    }

    /**
     * @return void
     */
    public function testGenerateHideUnrelatedWithNodeKey(): void
    {
        $treeData = [
            ['category_id' => 10, 'name' => 'A', 'children' => [['category_id' => 11, 'name' => 'A1', 'children' => []]]],
            ['category_id' => 20, 'name' => 'B', 'children' => [['category_id' => 21, 'name' => 'B1', 'children' => []]]],
        ];

        $output = $this->tree->generate($treeData, [
            'indent' => false,
            'hideUnrelated' => true,
            'nodeKey' => 'category_id',
            'treePath' => [20],
        ]);
        $this->assertSame('<ul><li>A</li><li>B<ul><li>B1</li></ul></li></ul>', $output);
    }

    /**
     * @param array $data
     * @return string
     */
    protected function myCallbackEntity(array $data): string
    {
        /** @var \Cake\ORM\Entity $entity */
        $entity = $data['data'];

        return h($entity->name) . ($data['activePathElement'] ? ' (active)' : '');
    }
}
