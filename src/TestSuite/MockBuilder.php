<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.8.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

loadPHPUnitAliases();

use PHPUnit\Framework\MockObject\MockBuilder as BaseMockBuilder;

/**
 * PHPUnit MockBuilder with muted Reflection errors
 *
 * @internal
 */
class MockBuilder extends BaseMockBuilder
{
    /**
     * @inheritDoc
     */
    public function getMock()
    {
        static::setSupressedErrorHandler();

        try {
            return parent::getMock();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Set error handler to supress `ReflectionType::__toString()` deprecation warning
     *
     * @return void
     */
    public static function setSupressedErrorHandler()
    {
        $previousHandler = set_error_handler(function ($code, $description, $file = null, $line = null, $context = null) use (&$previousHandler) {
            if (($code & E_DEPRECATED) && ($description === 'Function ReflectionType::__toString() is deprecated')) {
                return true;
            }

            return $previousHandler($code, $description, $file, $line, $context);
        });
    }
}
