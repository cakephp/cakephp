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
 * TagsTranslationsFixture
 */
class TagsTranslationsFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'null' => false, 'autoIncrement' => true],
        'locale' => ['type' => 'string', 'null' => false],
        'name' => ['type' => 'string', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['locale' => 'en_us', 'name' => 'tag 1 translated into en_us'],
        ['locale' => 'en_us', 'name' => 'tag 2 translated into en_us'],
        ['locale' => 'en_us', 'name' => 'tag 3 translated into en_us']
    ];
}
