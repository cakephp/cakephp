<?php
declare(strict_types=1);

namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use TestApp\Database\Point;

class PointsFixture extends TestFixture
{
    public $fields = [
        'id' => ['type' => 'integer'],
        'pt' => ['type' => 'point', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    public function init(): void
    {
        parent::init();

        $this->records = [
            [
                'id' => 1,
                'pt' => new Point(10, 20),
            ],
        ];
    }
}
