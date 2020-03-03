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
 * Class CommentsTranslationsFixture
 */
class CommentsTranslationsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'locale' => ['type' => 'string', 'null' => false],
        'comment' => 'text',
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id', 'locale']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['locale' => 'eng', 'id' => 1, 'comment' => 'Comment #1'],
        ['locale' => 'eng', 'id' => 2, 'comment' => 'Comment #2'],
        ['locale' => 'eng', 'id' => 3, 'comment' => 'Comment #3'],
        ['locale' => 'eng', 'id' => 4, 'comment' => 'Comment #4'],
        ['locale' => 'spa', 'id' => 4, 'comment' => 'Comentario #4'],
    ];
}
