<?php
declare(strict_types=1);

namespace TestApp\Dto;

/**
 * Simple readonly DTO without nested types.
 */
readonly class SimpleArticleDto
{
    /**
     * @param int $id
     * @param string $title
     * @param string|null $body
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $body = null,
    ) {
    }
}
