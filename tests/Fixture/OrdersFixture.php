<?php
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
 * @since         3.0.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrdersFixture
 */
class OrdersFixture extends TestFixture
{
    /**
     * {@inheritDoc}
     */
    public $table = 'orders';

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'product_category' => ['type' => 'integer', 'null' => false],
        'product_id' => ['type' => 'integer', 'null' => false],
        '_indexes' => [
            'product_category' => [
                'type' => 'index',
                'columns' => ['product_category', 'product_id'],
            ],
        ],
        '_constraints' => [
            'primary' => [
                'type' => 'primary', 'columns' => ['id'],
            ],
            'product_category_fk' => [
                'type' => 'foreign',
                'columns' => ['product_category', 'product_id'],
                'references' => ['products', ['category', 'id']],
                'update' => 'cascade',
                'delete' => 'cascade',
            ],
        ],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['product_category' => 1, 'product_id' => 1],
    ];
}
