<?php
namespace Cake\Test\Fixture;;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class ArticlesTranslationsFixture
 *
 */
class ArticlesMoreTranslationsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'locale' => ['type' => 'string', 'null' => false],
        'title' => ['type' => 'string', 'null' => false],
        'subtitle' => ['type' => 'string', 'null' => false],
        'body' => 'text',
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id', 'locale']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['locale' => 'eng', 'id' => 1, 'title' => 'Title #1', 'subtitle' => 'SubTitle #1', 'body' => 'Content #1'],
        ['locale' => 'deu', 'id' => 1, 'title' => 'Titel #1', 'subtitle' => 'SubTitel #1', 'body' => 'Inhalt #1'],
        ['locale' => 'cze', 'id' => 1, 'title' => 'Titulek #1', 'subtitle' => 'SubTitulek #1', 'body' => 'Obsah #1'],
        ['locale' => 'eng', 'id' => 2, 'title' => 'Title #2', 'subtitle' => 'SubTitle #2', 'body' => 'Content #2'],
        ['locale' => 'deu', 'id' => 2, 'title' => 'Titel #2', 'subtitle' => 'SubTitel #2', 'body' => 'Inhalt #2'],
        ['locale' => 'cze', 'id' => 2, 'title' => 'Titulek #2', 'subtitle' => 'SubTitulek #2', 'body' => 'Obsah #2'],
        ['locale' => 'eng', 'id' => 3, 'title' => 'Title #3', 'subtitle' => 'SubTitle #3', 'body' => 'Content #3'],
        ['locale' => 'deu', 'id' => 3, 'title' => 'Titel #3', 'subtitle' => 'SubTitel #3', 'body' => 'Inhalt #3'],
        ['locale' => 'cze', 'id' => 3, 'title' => 'Titulek #3', 'subtitle' => 'SubTitulek #3', 'body' => 'Obsah #3'],
    ];
}
