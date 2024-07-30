<?php
declare(strict_types=1);

namespace TestApp\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class FeaturedTagsFixture extends TestFixture
{
    /**
     * Table property
     */
    public string $table = 'featured_tags';

    /**
     * Records property
     */
    public array $records = [
        ['tag_id' => 1, 'priority' => 1.0],
        ['tag_id' => 2, 'priority' => 0.7],
    ];
}
