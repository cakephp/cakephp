<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.0.7
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class ProductOrdersFixture
 *
 */
class ProductOrdersFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'product_category' => ['type' => 'integer', 'null' => false],
        'product_id' => ['type' => 'integer', 'null' => false],
        'customer_id' => ['type' => 'integer', 'null' => false],
        '_indexes' => [
            'product_category' => [
                'type' => 'index',
                'columns' => ['product_category', 'product_id']
            ],
            'customer_id' => [
                'type' => 'index',
                'columns' => ['customer_id']
            ]
        ],
        '_constraints' => [
            'primary' => [
                'type' => 'primary', 'columns' => ['id']
            ],
            'product_order_ibfk_1' => [
                'type' => 'foreign',
                'columns' => ['product_category', 'product_id'],
                'references' => ['product', ['category', 'id']],
                'update' => 'cascade',
                'delete' => 'cascade',
            ],
            'product_order_ibfk_2' => [
                'type' => 'foreign',
                'columns' => ['customer_id'],
                'references' => ['customer', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade',
            ]
        ]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['product_category' => 1, 'product_id' => 1, 'customer_id' => 1]
    ];
}
