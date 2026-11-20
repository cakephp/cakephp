<?php
declare(strict_types=1);

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
 * @since         5.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Behavior;

use Cake\TestSuite\TestCase;

class NormalizeBehaviorTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = ['core.Articles', 'core.Posts', 'core.Users'];

    public function testTrimAndEmptyToNull(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->addBehavior('Normalize');

        $entity = $articles->newEntity([
            'title' => "  A title \n",
            'body' => '   ',
            'author_id' => ' 1 ',
            'published' => ' Y ',
        ]);

        $this->assertSame('A title', $entity->title);
        $this->assertNull($entity->body);
        $this->assertSame(1, $entity->author_id);
        // published has a default but is nullable
        $this->assertSame('Y', $entity->published);

        $entity = $articles->newEntity(['title' => '', 'published' => '']);
        $this->assertNull($entity->title);
        $this->assertNull($entity->published);
    }

    public function testNotNullableColumnKeepsEmptyString(): void
    {
        $posts = $this->getTableLocator()->get('Posts');
        $posts->addBehavior('Normalize');

        $entity = $posts->newEntity(['title' => '   ', 'body' => '  ']);
        $this->assertSame('', $entity->title);
        $this->assertNull($entity->body);
    }

    public function testRunsBeforeValidation(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->addBehavior('Normalize');
        $articles->getValidator()->notBlank('title')->maxLength('body', 3);

        $entity = $articles->newEntity(['title' => '   ', 'body' => '  abc  ']);
        // The blank title is null now, so the field fails the empty check.
        $this->assertArrayHasKey('_empty', $entity->getError('title'));
        $this->assertEmpty($entity->getError('body'));
        $this->assertSame('abc', $entity->body);
    }

    public function testPatchEntity(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->addBehavior('Normalize');

        $entity = $articles->get(1);
        $articles->patchEntity($entity, ['title' => ' First Article ', 'body' => '']);

        $this->assertSame('First Article', $entity->title);
        $this->assertFalse($entity->isDirty('title'));
        $this->assertNull($entity->body);
        $this->assertTrue($entity->isDirty('body'));
    }

    public function testOptions(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $users->addBehavior('Normalize', [
            'emptyToNull' => false,
            'exclude' => ['password'],
        ]);

        $entity = $users->newEntity(['username' => '   ', 'password' => ' secret ']);
        $this->assertSame('', $entity->username);
        $this->assertSame(' secret ', $entity->password);

        $users->behaviors()->get('Normalize')->setConfig(['trim' => false, 'emptyToNull' => true]);
        $entity = $users->newEntity(['username' => '  ', 'password' => '']);
        $this->assertSame('  ', $entity->username);
        $this->assertSame('', $entity->password);

        $entity = $users->newEntity(['username' => '']);
        $this->assertNull($entity->username);
    }

    public function testFieldsOption(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->addBehavior('Normalize', ['fields' => ['title', 'virtual']]);

        $entity = $articles->newEntity(['title' => ' a ', 'body' => ' b ', 'virtual' => '  ']);
        $this->assertSame('a', $entity->title);
        $this->assertSame(' b ', $entity->body);
        // Not a column, so it can't be nullable.
        $this->assertSame('', $entity->virtual);
    }

    public function testDisablePerCall(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->addBehavior('Normalize');

        $entity = $articles->newEntity(['title' => ' a ', 'body' => ''], ['normalize' => false]);
        $this->assertSame(' a ', $entity->title);
        $this->assertSame('', $entity->body);
    }

    public function testNonStringValuesAreIgnored(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->addBehavior('Normalize');

        $entity = $articles->newEntity(['title' => ['nope'], 'body' => null]);
        $this->assertNull($entity->title);
        $this->assertNull($entity->body);
    }

    public function testAssociatedData(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $authors = $this->getTableLocator()->get('Authors');
        $articles->belongsTo('Authors');
        $articles->addBehavior('Normalize');
        $authors->addBehavior('Normalize');

        $entity = $articles->newEntity(
            ['title' => ' a ', 'author' => ['name' => ' Author ']],
            ['associated' => ['Authors']],
        );
        $this->assertSame('a', $entity->title);
        $this->assertSame('Author', $entity->author->name);
    }

    public function testNewEntities(): void
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->addBehavior('Normalize');

        $entities = $articles->newEntities([['title' => ' a '], ['title' => '']]);
        $this->assertSame('a', $entities[0]->title);
        $this->assertNull($entities[1]->title);
    }
}
