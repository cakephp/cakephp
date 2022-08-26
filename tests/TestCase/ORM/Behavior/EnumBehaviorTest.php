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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Behavior;

use Cake\TestSuite\TestCase;
use TestApp\Model\Enum\ArticleStatus;

/**
 * EnumBehavior test case
 */
class EnumBehaviorTest extends TestCase
{
    /**
     * @var \Cake\ORM\Table
     */
    protected $articles;

    protected function setUp(): void
    {
        parent::setUp();
        $articles = $this->getTableLocator()->get('Articles');
        $this->articles = $articles->addBehavior('Enum', [
            'fieldMap' => [
                'published' => ArticleStatus::class,
            ],
        ]);
    }

    /**
     * Check adding entity fields with an enum instance
     */
    public function test_add_with_enum(): void
    {
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $this->articles->newEntity([
            'author_id' => 1,
            'title' => 'My Title',
            'body' => 'My post',
            'published' => ArticleStatus::PUBLISHED,
        ]);
        $saved = $this->articles->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);
    }

    /**
     * Check adding entity fields with scalar value representing enum
     */
    public function test_add_with_scalar_value(): void
    {
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $this->articles->newEntity([
            'author_id' => 1,
            'title' => 'My Title',
            'body' => 'My post',
            'published' => 'Y',
        ]);
        $saved = $this->articles->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);
    }

    /**
     * Check to get an entity and automatically transform field to an enum instance
     */
    public function test_get(): void
    {
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $this->articles->get(1);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);
    }

    /**
     * Check updating an entity via an enum instance
     */
    public function test_update_with_enum(): void
    {
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $this->articles->get(1);
        $entity->published = ArticleStatus::UNPUBLISHED;
        $this->articles->save($entity);
        $this->assertSame(ArticleStatus::UNPUBLISHED, $entity->published);
    }

    /**
     * Check updating an entity with scalar value representing enum
     */
    public function test_update_with_scalar_value(): void
    {
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $this->articles->get(1);
        $entity->published = 'N';
        $this->articles->save($entity);
        $this->assertSame(ArticleStatus::UNPUBLISHED, $entity->published);
    }
}
