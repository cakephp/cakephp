<?php
declare(strict_types=1);

namespace TestApp\Dto;

use Cake\I18n\DateTime;

/**
 * DTO with DateTime fields to test object pass-through.
 */
readonly class ArticleWithDatesDto
{
    /**
     * @param int $id
     * @param string $title
     * @param \Cake\I18n\DateTime|null $created
     * @param \Cake\I18n\DateTime|null $modified
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?DateTime $created = null,
        public ?DateTime $modified = null,
    ) {
    }
}
