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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\I18n\DateTime;
use Cake\ORM\DtoMapper;
use Cake\TestSuite\TestCase;
use TestApp\Dto\ArticleDto;
use TestApp\Dto\ArticleWithDatesDto;
use TestApp\Dto\AuthorDto;
use TestApp\Dto\CommentDto;
use TestApp\Dto\SimpleArticleDto;

/**
 * DtoMapper test case.
 */
class DtoMapperTest extends TestCase
{
    protected DtoMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new DtoMapper();
        DtoMapper::clearCache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        DtoMapper::clearCache();
    }

    public function testMapSimpleDto(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'body' => 'Test Body',
        ];

        $dto = $this->mapper->map($data, SimpleArticleDto::class);

        $this->assertInstanceOf(SimpleArticleDto::class, $dto);
        $this->assertSame(1, $dto->id);
        $this->assertSame('Test Article', $dto->title);
        $this->assertSame('Test Body', $dto->body);
    }

    public function testMapWithNullableField(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
        ];

        $dto = $this->mapper->map($data, SimpleArticleDto::class);

        $this->assertInstanceOf(SimpleArticleDto::class, $dto);
        $this->assertSame(1, $dto->id);
        $this->assertSame('Test Article', $dto->title);
        $this->assertNull($dto->body);
    }

    public function testMapWithDefaultValue(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
        ];

        $dto = $this->mapper->map($data, ArticleDto::class);

        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertSame([], $dto->comments);
    }

    public function testMapNestedDto(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'author' => [
                'id' => 10,
                'name' => 'John Doe',
            ],
        ];

        $dto = $this->mapper->map($data, ArticleDto::class);

        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertInstanceOf(AuthorDto::class, $dto->author);
        $this->assertSame(10, $dto->author->id);
        $this->assertSame('John Doe', $dto->author->name);
    }

    public function testMapNestedDtoNull(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'author' => null,
        ];

        $dto = $this->mapper->map($data, ArticleDto::class);

        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertNull($dto->author);
    }

    public function testMapCollectionOfDtos(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'comments' => [
                ['id' => 1, 'comment' => 'First comment', 'article_id' => 1, 'user_id' => 1],
                ['id' => 2, 'comment' => 'Second comment', 'article_id' => 1, 'user_id' => 2],
            ],
        ];

        $dto = $this->mapper->map($data, ArticleDto::class);

        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertCount(2, $dto->comments);
        $this->assertInstanceOf(CommentDto::class, $dto->comments[0]);
        $this->assertInstanceOf(CommentDto::class, $dto->comments[1]);
        $this->assertSame('First comment', $dto->comments[0]->comment);
        $this->assertSame('Second comment', $dto->comments[1]->comment);
    }

    public function testMapCollectionEmpty(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'comments' => [],
        ];

        $dto = $this->mapper->map($data, ArticleDto::class);

        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertSame([], $dto->comments);
    }

    public function testMapWithExtraFields(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'body' => 'Test Body',
            'extra_field' => 'ignored',
            'another_field' => 123,
        ];

        $dto = $this->mapper->map($data, SimpleArticleDto::class);

        $this->assertInstanceOf(SimpleArticleDto::class, $dto);
        $this->assertSame(1, $dto->id);
        $this->assertSame('Test Article', $dto->title);
        $this->assertSame('Test Body', $dto->body);
    }

    public function testMapComplexNestedStructure(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'body' => 'Test Body',
            'author' => [
                'id' => 10,
                'name' => 'Jane Doe',
            ],
            'comments' => [
                ['id' => 1, 'comment' => 'Great article!', 'article_id' => 1, 'user_id' => 5],
                ['id' => 2, 'comment' => 'Thanks for sharing', 'article_id' => 1, 'user_id' => 6],
                ['id' => 3, 'comment' => 'Very helpful', 'article_id' => 1, 'user_id' => 7],
            ],
        ];

        $dto = $this->mapper->map($data, ArticleDto::class);

        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertSame(1, $dto->id);
        $this->assertSame('Test Article', $dto->title);
        $this->assertSame('Test Body', $dto->body);

        $this->assertInstanceOf(AuthorDto::class, $dto->author);
        $this->assertSame(10, $dto->author->id);
        $this->assertSame('Jane Doe', $dto->author->name);

        $this->assertCount(3, $dto->comments);
        $this->assertSame('Great article!', $dto->comments[0]->comment);
        $this->assertSame(5, $dto->comments[0]->user_id);
    }

    public function testCacheIsUsed(): void
    {
        $data = ['id' => 1, 'title' => 'Test', 'body' => 'Body'];

        // First call populates cache
        $this->mapper->map($data, SimpleArticleDto::class);

        // Second call should use cache
        $dto = $this->mapper->map($data, SimpleArticleDto::class);

        $this->assertInstanceOf(SimpleArticleDto::class, $dto);
    }

    public function testClearCache(): void
    {
        $data = ['id' => 1, 'title' => 'Test', 'body' => 'Body'];

        $this->mapper->map($data, SimpleArticleDto::class);

        DtoMapper::clearCache();

        // Should still work after clearing cache
        $dto = $this->mapper->map($data, SimpleArticleDto::class);

        $this->assertInstanceOf(SimpleArticleDto::class, $dto);
    }

    public function testMapWithDateTimeObjects(): void
    {
        $created = new DateTime('2024-01-15 10:30:00');
        $modified = new DateTime('2024-06-20 14:45:00');

        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'created' => $created,
            'modified' => $modified,
        ];

        $dto = $this->mapper->map($data, ArticleWithDatesDto::class);

        $this->assertInstanceOf(ArticleWithDatesDto::class, $dto);
        $this->assertSame(1, $dto->id);
        $this->assertSame('Test Article', $dto->title);
        // DateTime objects should be passed through, not mapped
        $this->assertSame($created, $dto->created);
        $this->assertSame($modified, $dto->modified);
    }

    public function testMapWithNullDateTime(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Article',
            'created' => null,
            'modified' => null,
        ];

        $dto = $this->mapper->map($data, ArticleWithDatesDto::class);

        $this->assertInstanceOf(ArticleWithDatesDto::class, $dto);
        $this->assertNull($dto->created);
        $this->assertNull($dto->modified);
    }
}
