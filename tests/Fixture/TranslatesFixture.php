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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class TranslateFixture
 *
 */
class TranslatesFixture extends TestFixture
{

    /**
     * table property
     *
     * @var string
     */
    public $table = 'i18n';

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'locale' => ['type' => 'string', 'length' => 6, 'null' => false],
        'model' => ['type' => 'string', 'null' => false],
        'foreign_key' => ['type' => 'integer', 'null' => false],
        'field' => ['type' => 'string', 'null' => false],
        'content' => ['type' => 'text'],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['locale' => 'eng', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Title #1'],
        ['locale' => 'eng', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'body', 'content' => 'Content #1'],
        ['locale' => 'eng', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'description', 'content' => 'Description #1'],
        ['locale' => 'spa', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'body', 'content' => 'Contenido #1'],
        ['locale' => 'spa', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'description', 'content' => ''],
        ['locale' => 'deu', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titel #1'],
        ['locale' => 'deu', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'body', 'content' => 'Inhalt #1'],
        ['locale' => 'cze', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titulek #1'],
        ['locale' => 'cze', 'model' => 'Articles', 'foreign_key' => 1, 'field' => 'body', 'content' => 'Obsah #1'],
        ['locale' => 'eng', 'model' => 'Articles', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Title #2'],
        ['locale' => 'eng', 'model' => 'Articles', 'foreign_key' => 2, 'field' => 'body', 'content' => 'Content #2'],
        ['locale' => 'deu', 'model' => 'Articles', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Titel #2'],
        ['locale' => 'deu', 'model' => 'Articles', 'foreign_key' => 2, 'field' => 'body', 'content' => 'Inhalt #2'],
        ['locale' => 'cze', 'model' => 'Articles', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Titulek #2'],
        ['locale' => 'cze', 'model' => 'Articles', 'foreign_key' => 2, 'field' => 'body', 'content' => 'Obsah #2'],
        ['locale' => 'eng', 'model' => 'Articles', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Title #3'],
        ['locale' => 'eng', 'model' => 'Articles', 'foreign_key' => 3, 'field' => 'body', 'content' => 'Content #3'],
        ['locale' => 'deu', 'model' => 'Articles', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Titel #3'],
        ['locale' => 'deu', 'model' => 'Articles', 'foreign_key' => 3, 'field' => 'body', 'content' => 'Inhalt #3'],
        ['locale' => 'cze', 'model' => 'Articles', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Titulek #3'],
        ['locale' => 'cze', 'model' => 'Articles', 'foreign_key' => 3, 'field' => 'body', 'content' => 'Obsah #3'],
        ['locale' => 'eng', 'model' => 'Comments', 'foreign_key' => 1, 'field' => 'comment', 'content' => 'Comment #1'],
        ['locale' => 'eng', 'model' => 'Comments', 'foreign_key' => 2, 'field' => 'comment', 'content' => 'Comment #2'],
        ['locale' => 'eng', 'model' => 'Comments', 'foreign_key' => 3, 'field' => 'comment', 'content' => 'Comment #3'],
        ['locale' => 'eng', 'model' => 'Comments', 'foreign_key' => 4, 'field' => 'comment', 'content' => 'Comment #4'],
        ['locale' => 'spa', 'model' => 'Comments', 'foreign_key' => 4, 'field' => 'comment', 'content' => 'Comentario #4'],
        ['locale' => 'eng', 'model' => 'Authors', 'foreign_key' => 1, 'field' => 'name', 'content' => 'May-rianoh']
    ];
}
