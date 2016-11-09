<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\Database\Schema\TableSchema;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * SessionFixture
 */
class SessionsFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'string', 'length' => 128],
        'data' => ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => true],
        'expires' => ['type' => 'integer', 'length' => 11, 'null' => true],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [];
}
