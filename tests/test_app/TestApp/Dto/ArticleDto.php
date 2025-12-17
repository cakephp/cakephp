<?php
declare(strict_types=1);

namespace TestApp\Dto;

use Cake\ORM\Attribute\CollectionOf;

/**
 * Simple readonly DTO for Article.
 */
readonly class ArticleDto
{
    /**
     * @param int $id
     * @param string $title
     * @param string|null $body
     * @param \TestApp\Dto\AuthorDto|null $author
     * @param array<\TestApp\Dto\CommentDto> $comments
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $body = null,
        public ?AuthorDto $author = null,
        #[CollectionOf(CommentDto::class)]
        public array $comments = [],
    ) {
    }
}
