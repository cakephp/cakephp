<?php
declare(strict_types=1);

namespace TestApp\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ImportsFixture extends TestFixture
{
    /**
     * Import property
     *
     * @var mixed
     */
    public $import = ['table' => 'posts', 'connection' => 'test'];

    /**
     * Records property
     *
     * @var array
     */
    public $records = [
        ['title' => 'Hello!', 'body' => 'Hello world!'],
    ];
}
