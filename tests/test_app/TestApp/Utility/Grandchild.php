<?php
declare(strict_types=1);

namespace TestApp\Utility;

class Grandchild extends Child
{
    public $listProperty = ['Four', 'Five'];

    public $assocProperty = [
        'Green' => ['apple'],
        'Yellow' => ['banana'],
    ];

    public $nestedProperty = [
        'Red' => [
            'citrus' => 'blood orange',
        ],
        'Green' => [
            'citrus' => 'key lime',
        ],
    ];
}
