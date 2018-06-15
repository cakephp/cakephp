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
 * ProductsFixture
 */
class ProductsFixture extends TestFixture
{
    /**
     * {@inheritDoc}
     */
    public $table = 'products';

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'category' => ['type' => 'integer', 'null' => false],
        'name' => ['type' => 'string', 'null' => false],
        'price' => ['type' => 'integer'],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['category', 'id']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['id' => 1, 'category' => 1, 'name' => 'First product', 'price' => 10],
        ['id' => 2, 'category' => 2, 'name' => 'Second product', 'price' => 20],
        ['id' => 3, 'category' => 3, 'name' => 'Third product', 'price' => 30],
    ];
}
