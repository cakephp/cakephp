<?php
declare(strict_types=1);

namespace TestApp\View;

use Cake\View\View;

/**
 * Test View class with default config to test shallow merge behavior
 */
class TestViewWithDefaultConfig extends View
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'myOption' => ['value' => ['sub' => 'val'], 'non-assoc' => ['x', 'y']],
    ];
}
