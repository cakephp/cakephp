<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.4.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @deprecated Backwards compatibility test for alias. Will be removed in 5.0
 */
namespace Cake\Test\TestCase\Cache;

use Cake\TestSuite\TestCase;

/**
 * InvalidArgumentExceptionTest class
 */
class InvalidArgumentExceptionTest extends TestCase
{
    /**
     * tests That the old/deprecated class is properly aliased to the new one.
     */
    public function testThrowingOldException(): void
    {
        $this->expectException(\Cake\Cache\InvalidArgumentException::class);

        throw new \Cake\Cache\InvalidArgumentException('I should be the old one, aliased.');
    }
}
