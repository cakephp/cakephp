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

    public function mergeVars($properties, $options = [])
    {
        return $this->_mergeVars($properties, $options);
    }
}
