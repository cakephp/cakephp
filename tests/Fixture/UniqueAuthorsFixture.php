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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Tables of unique author ids
 */
class UniqueAuthorsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'first_author_id' => ['type' => 'integer', 'null' => true],
        'second_author_id' => ['type' => 'integer', 'null' => false],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'nullable_non_nullable_unique' => ['type' => 'unique', 'columns' => ['first_author_id', 'second_author_id']],
        ],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['first_author_id' => null, 'second_author_id' => 1],
    ];
}
