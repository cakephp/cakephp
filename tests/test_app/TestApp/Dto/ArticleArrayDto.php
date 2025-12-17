<?php
declare(strict_types=1);

namespace TestApp\Dto;

/**
 * DTO with createFromArray factory method (cakephp-dto style).
 */
readonly class ArticleArrayDto
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $body = null,
        public ?AuthorArrayDto $author = null,
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
            title: $data['title'],
            body: $data['body'] ?? null,
            author: isset($data['author']) && is_array($data['author'])
                ? AuthorArrayDto::createFromArray($data['author'], $ignoreMissing)
                : null,
        );
    }
}
