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
     * Whether to use I18n functions for translating default error messages
     *
     * @var bool
     */
    protected bool $_useI18n = false;
}
