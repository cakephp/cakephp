<?php
declare(strict_types=1);

namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use TestApp\Database\ColumnSchemaAwareTypeValueObject;

class ColumnSchemaAwareTypeValuesFixture extends TestFixture
{
    public function init(): void
    {
        parent::init();

        $this->records = [
            [
                'val' => new ColumnSchemaAwareTypeValueObject('THIS TEXT SHOULD BE PROCESSED VIA A CUSTOM TYPE'),
            ],
            [
                'val' => 'THIS TEXT ALSO SHOULD BE PROCESSED VIA A CUSTOM TYPE',
            ],
        ];
    }
}
