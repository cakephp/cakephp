<?php
declare(strict_types=1);

namespace TestApp\View;

use Cake\Core\InstanceConfigTrait;
use Cake\View\StringTemplateTrait;

class TestStringTemplate
{
    use InstanceConfigTrait;
    use StringTemplateTrait;

    /**
     * @var array
     */
    protected $_defaultConfig = [];
}
