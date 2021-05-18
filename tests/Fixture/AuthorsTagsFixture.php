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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AuthorsTags fixture
 */
class AuthorsTagsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'author_id' => ['type' => 'integer', 'null' => false],
        'tag_id' => ['type' => 'integer', 'null' => false],
        '_constraints' => [
            'unique_tag' => ['type' => 'primary', 'columns' => ['author_id', 'tag_id']],
            'author_id_fk' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
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
        ['author_id' => 3, 'tag_id' => 1],
        ['author_id' => 3, 'tag_id' => 2],
        ['author_id' => 2, 'tag_id' => 1],
        ['author_id' => 2, 'tag_id' => 3],
    ];
}
