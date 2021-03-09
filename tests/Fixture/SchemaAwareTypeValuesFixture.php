<?php
declare(strict_types=1);

namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use TestApp\Database\SchemaAwareTypeValueObject;

class SchemaAwareTypeValuesFixture extends TestFixture
{
    public $fields = [
        'id' => ['type' => 'integer'],
        'val' => ['type' => 'text', 'null' => false, 'comment' => 'Fixture comment'],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public function init(): void
    {
        parent::init();

        $this->records = [
            [
                'val' => new SchemaAwareTypeValueObject('THIS TEXT SHOULD BE PROCESSED VIA A CUSTOM TYPE'),
            ],
            [
                'val' => 'THIS TEXT ALSO SHOULD BE PROCESSED VIA A CUSTOM TYPE',
            ],
        ];
    }
}
