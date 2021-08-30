<?php
declare(strict_types=1);

namespace TestApp\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * This class allows testing the fixture data insertion when the properties
 * $fields and $import are not set
 */
class LettersFixture extends TestFixture
{
    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['letter' => 'a'],
        ['letter' => 'b'],
        ['letter' => 'c'],
    ];
}
