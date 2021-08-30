<?php
declare(strict_types=1);

namespace TestApp\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesFixture extends TestFixture
{
    /**
     * Table property
     *
     * @var string
     */
    public $table = 'articles';

    /**
     * Fields array
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'string', 'length' => '255'],
        'created' => ['type' => 'datetime'],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    /**
     * Records property
     *
     * @var array
     */
    public $records = [
        ['name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'],
        ['name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'],
        ['name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00'],
    ];
}
