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
 * BinaryUuiditemsBinaryUuidtagsFixture
 */
class BinaryUuiditemsBinaryUuidtagsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'binary_uuiditem_id' => ['type' => 'binaryuuid', 'null' => false],
        'binary_uuidtag_id' => ['type' => 'binaryuuid', 'null' => false],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
            'unique_item_tag' => [
                'type' => 'unique',
                'columns' => ['binary_uuiditem_id', 'binary_uuidtag_id'],
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
