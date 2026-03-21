<?php
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
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Fixture for testing string aggregation functions (STRING_AGG / GROUP_CONCAT)
 */
class StringAggItemsFixture extends TestFixture
{
    /**
     * Table property
     *
     * @var string
     */
    public string $table = 'string_agg_items';

    /**
     * Fields property
     *
     * @var array
     */
    public array $fields = [
        'id' => ['type' => 'integer'],
        'category' => ['type' => 'string', 'length' => 255],
        'name' => ['type' => 'string', 'length' => 255],
        'sort_order' => ['type' => 'integer', 'default' => 0],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    /**
     * Records property
     *
     * @var array
     */
    public array $records = [
        ['category' => 'A', 'name' => 'Item 1', 'sort_order' => 1],
        ['category' => 'A', 'name' => 'Item 2', 'sort_order' => 2],
        ['category' => 'A', 'name' => 'Item 3', 'sort_order' => 3],
        ['category' => 'B', 'name' => 'Item 4', 'sort_order' => 1],
        ['category' => 'B', 'name' => 'Item 5', 'sort_order' => 2],
        ['category' => 'C', 'name' => 'Item 6', 'sort_order' => 1],
    ];
}
