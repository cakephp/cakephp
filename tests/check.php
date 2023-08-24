<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

if (!defined('PHPUNIT_TESTSUITE') || !PHPUNIT_TESTSUITE) {
    return;
}

$assertions = (int)ini_get('zend.assertions');
if ($assertions !== 1) {
    throw new RuntimeException('Assertions are not activated, but needed for tests to run. Please set respective directives in your php.ini (`zend.assertions = 1`).');
}
