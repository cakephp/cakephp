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
 * @since         4.1.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BinaryUuidItemsBinaryUuidTagsFixture
 */
class BinaryUuidItemsBinaryUuidTagsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'binary_uuid_item_id' => ['type' => 'binaryuuid', 'null' => false],
        'binary_uuid_tag_id' => ['type' => 'binaryuuid', 'null' => false],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'unique_item_tag' => [
                'type' => 'unique',
                'columns' => ['binary_uuid_item_id', 'binary_uuid_tag_id'],
            ],
        ],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [];
}
