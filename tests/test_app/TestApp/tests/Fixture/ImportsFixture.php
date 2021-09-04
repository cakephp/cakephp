<?php
declare(strict_types=1);

namespace TestApp\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ImportsFixture extends TestFixture
{
    /**
     * Import property
     *
     * @var array|null
     */
    public ?array $import = ['table' => 'posts', 'connection' => 'test'];

    /**
     * Records property
     *
     * @var array
     */
    public array $records = [
        ['title' => 'Hello!', 'body' => 'Hello world!'],
    ];
}
