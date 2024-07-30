<?php
declare(strict_types=1);

namespace TestApp\Controller\Component;

use Cake\Controller\Component;

class BlueberryComponent extends Component
{
    /**
     * testName property
     *
     * @var string|null
     */
    public $testName;

    /**
     * initialize method
     */
    public function initialize(array $config): void
    {
        $this->testName = 'BlueberryComponent';
    }
}
