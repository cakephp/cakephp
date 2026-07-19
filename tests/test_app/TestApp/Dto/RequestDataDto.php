<?php
declare(strict_types=1);

namespace TestApp\Dto;

class RequestDataDto
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return static
     */
    public static function createFromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
