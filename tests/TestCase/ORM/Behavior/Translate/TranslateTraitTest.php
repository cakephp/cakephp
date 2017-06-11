<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Behavior\Translate;

use Cake\ORM\Behavior\Translate\TranslateTrait;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

class TestEntity extends Entity
{
    use TranslateTrait;
}

/**
 * Translate behavior test case
 */
class TranslateTraitTest extends TestCase
{

    /**
     * Tests that missing translation entries are created automatically
     *
     * @return void
     */
    public function testTranslationCreate()
    {
        $entity = new TestEntity;
        $entity->translation('eng')->set('title', 'My Title');
        $this->assertEquals('My Title', $entity->translation('eng')->get('title'));

        $this->assertTrue($entity->dirty('_translations'));

        $entity->translation('spa')->set('body', 'Contenido');
        $this->assertEquals('My Title', $entity->translation('eng')->get('title'));
        $this->assertEquals('Contenido', $entity->translation('spa')->get('body'));
    }

    /**
     * Tests that modifying existing translation entries work
     *
     * @return void
     */
    public function testTranslationModify()
    {
        $entity = new TestEntity;
        $entity->set('_translations', [
            'eng' => new Entity(['title' => 'My Title']),
            'spa' => new Entity(['title' => 'Titulo'])
        ]);
        $this->assertEquals('My Title', $entity->translation('eng')->get('title'));
        $this->assertEquals('Titulo', $entity->translation('spa')->get('title'));
    }

    /**
     * Tests empty translations.
     *
     * @return void
     */
    public function testTranslationEmpty()
    {
        $entity = new TestEntity;
        $entity->set('_translations', [
            'eng' => new Entity(['title' => 'My Title']),
            'spa' => new Entity(['title' => 'Titulo'])
        ]);
        $this->assertTrue($entity->translation('pol')->isNew());
        $this->assertInstanceOf('Cake\Test\TestCase\ORM\Behavior\Translate\TestEntity', $entity->translation('pol'));
    }

    /**
     * Tests that just accessing the translation will mark the property as dirty, this
     * is to facilitate the saving process by not having to remember to mark the property
     * manually
     *
     * @return void
     */
    public function testTranslationDirty()
    {
        $entity = new TestEntity;
        $entity->set('_translations', [
            'eng' => new Entity(['title' => 'My Title']),
            'spa' => new Entity(['title' => 'Titulo'])
        ]);
        $entity->clean();
        $this->assertEquals('My Title', $entity->translation('eng')->get('title'));
        $this->assertTrue($entity->dirty('_translations'));
    }
}
