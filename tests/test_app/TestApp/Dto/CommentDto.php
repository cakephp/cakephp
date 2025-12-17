<?php
declare(strict_types=1);

namespace TestApp\Dto;

/**
 * Simple readonly DTO for Comment.
 */
readonly class CommentDto
{
    /**
     * @param int $id
     * @param string $comment
     * @param int $article_id
     * @param int $user_id
     */
    public function __construct(
        public int $id,
        public string $comment,
        public int $article_id,
        public int $user_id,
    ) {
    }
}
