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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\SaveOptionsBuilder;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * SaveOptionsBuilder test case.
 */
class SaveOptionsBuilderTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected $fixtures = [
        'core.Articles',
        'core.Authors',
        'core.Comments',
        'core.Users',
    ];

    /**
     * @var \Cake\ORM\Table
     */
    protected $table;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $connection = ConnectionManager::get('test');
        $this->table = new Table([
            'table' => 'articles',
            'connection' => $connection,
        ]);

        $this->table->belongsTo('Authors');
        $this->table->hasMany('Comments');
        $this->table->Comments->belongsTo('Users');
    }

    /**
     * testAssociatedChecks
     */
    public function testAssociatedChecks(): void
    {
        $expected = [
            'associated' => [
                'Comments' => [],
            ],
        ];
        $builder = new SaveOptionsBuilder($this->table);
        $builder->associated(
            'Comments'
        );
        $result = $builder->toArray();
        $this->assertEquals($expected, $result);

        $expected = [
            'associated' => [
                'Comments' => [
                    'associated' => [
                        'Users' => [],
                    ],
                ],
            ],
        ];
        $builder = new SaveOptionsBuilder($this->table);
        $builder->associated(
            'Comments.Users'
        );
        $result = $builder->toArray();
        $this->assertEquals($expected, $result);

        try {
            $builder = new SaveOptionsBuilder($this->table);
            $builder->associated(
                'Comments.DoesNotExist'
            );
            $this->fail('No \RuntimeException throw for invalid association!');
        } catch (RuntimeException $e) {
        }

        $expected = [
            'associated' => [
                'Comments' => [
                    'associated' => [
                        (int)0 => 'Users',
                    ],
                ],
            ],
        ];
        $builder = new SaveOptionsBuilder($this->table);
        $builder->associated([
            'Comments' => [
                'associated' => [
                    'Users',
                ],
            ],
        ]);
        $result = $builder->toArray();
        $this->assertEquals($expected, $result);

        $expected = [
            'associated' => [
                'Authors' => [],
                'Comments' => [
                    'associated' => [
                        (int)0 => 'Users',
                    ],
                ],
            ],
        ];
        $builder = new SaveOptionsBuilder($this->table);
        $builder->associated([
            'Authors',
            'Comments' => [
                'associated' => [
                    'Users',
                ],
            ],
        ]);
        $result = $builder->toArray();
        $this->assertEquals($expected, $result);
    }

    /**
     * testBuilder
     */
    public function testBuilder(): void
    {
        $expected = [
            'associated' => [
                'Authors' => [],
                'Comments' => [
                    'associated' => [
                        (int)0 => 'Users',
                    ],
                ],
            ],
            'guard' => false,
            'checkRules' => false,
            'checkExisting' => true,
            'atomic' => true,
            'validate' => 'default',
        ];

        $builder = new SaveOptionsBuilder($this->table);
        $builder->associated([
            'Authors',
            'Comments' => [
                'associated' => [
                    'Users',
                ],
            ],
        ])
        ->guard(false)
        ->checkRules(false)
        ->checkExisting(true)
        ->atomic(true)
        ->validate('default');

        $result = $builder->toArray();
        $this->assertEquals($expected, $result);
    }

    /**
     * testParseOptionsArray
     */
    public function testParseOptionsArray(): void
    {
        $options = [
            'associated' => [
                'Authors' => [],
                'Comments' => [
                    'associated' => [
                        (int)0 => 'Users',
                    ],
                ],
            ],
            'guard' => false,
            'checkRules' => false,
            'checkExisting' => true,
            'atomic' => true,
            'validate' => 'default',
        ];

        $builder = new SaveOptionsBuilder($this->table, $options);
        $this->assertEquals($options, $builder->toArray());
    }

    /**
     * testSettingCustomOptions
     */
    public function testSettingCustomOptions(): void
    {
        $expected = [
            'myOption' => true,
        ];

        $builder = new SaveOptionsBuilder($this->table);
        $builder->set('myOption', true);
        $this->assertEquals($expected, $builder->toArray());
    }
}
