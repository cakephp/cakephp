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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class TagsShadowTranslationsFixture
 */
class TagsShadowTranslationsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'locale' => ['type' => 'string', 'null' => false],
        'name' => ['type' => 'string', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id', 'locale']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['locale' => 'eng', 'id' => 1, 'name' => 'tag1 in eng'],
        ['locale' => 'deu', 'id' => 1, 'name' => 'tag1 in deu'],
        ['locale' => 'cze', 'id' => 1, 'name' => 'tag1 in cze'],
        ['locale' => 'eng', 'id' => 2, 'name' => 'tag2 in eng'],
        ['locale' => 'deu', 'id' => 2, 'name' => 'tag2 in deu'],
        ['locale' => 'cze', 'id' => 2, 'name' => 'tag2 in cze'],
        ['locale' => 'eng', 'id' => 3, 'name' => 'tag3 in eng'],
        ['locale' => 'deu', 'id' => 3, 'name' => 'tag3 in deu'],
        ['locale' => 'cze', 'id' => 3, 'name' => 'tag3 in cze'],
    ];
}
