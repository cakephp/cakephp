<?php
declare(strict_types=1);

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

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use TestApp\Model\Entity\TranslateTestEntity;

/**
 * Translate behavior test case
 */
class TranslateTraitTest extends TestCase
{
    /**
     * Tests that translation() returns null for non-existent translations
     */
    public function testTranslationReturnsNull(): void
    {
        $entity = new TranslateTestEntity();
        $this->assertNull($entity->translation('eng'));
        $this->assertNull($entity->translation('spa'));
    }

    /**
     * Tests that translation() returns existing translations
     */
    public function testTranslationReturnsExisting(): void
    {
        $entity = new TranslateTestEntity();
        $entity->set('_translations', [
            'eng' => new Entity(['title' => 'My Title']),
            'spa' => new Entity(['title' => 'Titulo']),
        ]);
        $this->assertSame('My Title', $entity->translation('eng')->get('title'));
        $this->assertSame('Titulo', $entity->translation('spa')->get('title'));
        $this->assertNull($entity->translation('fra'));
    }

    /**
     * Tests that getOrCreateTranslation() creates missing translations
     */
    public function testGetOrCreateTranslation(): void
    {
        $entity = new TranslateTestEntity();
        $entity->getOrCreateTranslation('eng')->set('title', 'My Title');
        $this->assertSame('My Title', $entity->translation('eng')->get('title'));

        $this->assertTrue($entity->isDirty('_translations'));

        $entity->getOrCreateTranslation('spa')->set('body', 'Contenido');
        $this->assertSame('My Title', $entity->translation('eng')->get('title'));
        $this->assertSame('Contenido', $entity->translation('spa')->get('body'));
    }

    /**
     * Tests that getOrCreateTranslation() returns correct entity type
     */
    public function testGetOrCreateTranslationEntityType(): void
    {
        $entity = new TranslateTestEntity();
        $entity->set('_translations', [
            'eng' => new Entity(['title' => 'My Title']),
        ]);
        $translation = $entity->getOrCreateTranslation('pol');
        $this->assertTrue($translation->isNew());
        $this->assertInstanceOf(TranslateTestEntity::class, $translation);
    }

    /**
     * Tests that getOrCreateTranslation() marks _translations as dirty
     */
    public function testGetOrCreateTranslationDirty(): void
    {
        $entity = new TranslateTestEntity();
        $entity->set('_translations', [
            'eng' => new Entity(['title' => 'My Title']),
        ]);
        $entity->clean();
        $entity->getOrCreateTranslation('eng');
        $this->assertTrue($entity->isDirty('_translations'));
    }

    /**
     * Tests hasTranslation() method
     */
    public function testHasTranslation(): void
    {
        $entity = new TranslateTestEntity();
        $this->assertFalse($entity->hasTranslation('eng'));

        $entity->set('_translations', [
            'eng' => new Entity(['title' => 'My Title']),
            'spa' => new Entity(['title' => 'Titulo']),
        ]);
        $this->assertTrue($entity->hasTranslation('eng'));
        $this->assertTrue($entity->hasTranslation('spa'));
        $this->assertFalse($entity->hasTranslation('fra'));
    }
}
