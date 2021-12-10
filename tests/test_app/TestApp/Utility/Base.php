<?php
declare(strict_types=1);

namespace TestApp\Utility;

use Cake\Utility\MergeVariablesTrait;

class Base
{
    use MergeVariablesTrait;

    public $hasBoolean = false;

    public $listProperty = ['One'];

    public $assocProperty = ['Red'];

    /**
     * @param string[] $properties An array of properties and the merge strategy for them.
     * @param array<string, mixed> $options The options to use when merging properties.
     */
    public function mergeVars(array $properties, array $options = []): void
    {
        $this->_mergeVars($properties, $options);
    }
}
