<?php
declare(strict_types=1);

namespace TestApp\Dto;

/**
 * Author DTO with createFromArray factory method.
 */
readonly class AuthorArrayDto
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    /**
     * Create from array data.
     *
     * @param array $data The data array
     * @param bool $ignoreMissing Whether to ignore missing fields
     * @return self
     */
    public static function createFromArray(array $data, bool $ignoreMissing = false): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }
}
