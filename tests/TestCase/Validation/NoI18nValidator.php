<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Validation;

use Cake\Validation\Validator;

/**
 * Validator without I18n functions available
 */
class NoI18nValidator extends Validator
{
    /**
     * Disable the usage of I18n functions
     */
    public function __construct()
    {
        $this->_useI18n = false;
        $this->_providers = self::$_defaultProviders;
    }
}
