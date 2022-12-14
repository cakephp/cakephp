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
 * Class ArticlesTranslationsFixture
 */
class ArticlesMoreTranslationsFixture extends TestFixture
{
    /**
     * records property
     *
     * @var array
     */
    public array $records = [
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
