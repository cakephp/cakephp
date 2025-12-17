<?php
declare(strict_types=1);

namespace TestApp\Dto;

/**
 * Simple readonly DTO for Author.
 */
readonly class AuthorDto
{
    /**
     * @param int $id
     * @param string $name
     */
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
