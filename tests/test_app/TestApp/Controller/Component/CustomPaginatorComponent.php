<?php
declare(strict_types=1);

namespace TestApp\Controller\Component;

use Cake\Controller\Component\PaginatorComponent;

class CustomPaginatorComponent extends PaginatorComponent
{
    protected $_defaultConfig = [
        'page' => 1,
        'limit' => 20,
        'maxLimit' => 100,
        'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
    ];
}
