<?php
declare(strict_types=1);

namespace TestApp\Utility;

class Child extends Base
{
    public $hasBoolean = ['test'];

    public $listProperty = ['Two', 'Three'];

    public $assocProperty = [
        'Green' => ['lime'],
        'Orange',
    ];

    public $nestedProperty = [
        'Red' => [
            'apple' => 'gala',
        ],
        'Green' => [
            'citrus' => 'lime',
        ],
    ];
}
