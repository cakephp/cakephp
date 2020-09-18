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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\Configure;
use Cake\Core\ServiceConfig;
use Cake\TestSuite\TestCase;

/**
 * ServiceConfigTest
 */
class ServiceConfigTest extends TestCase
{
    public function testGet()
    {
        Configure::write('first', 'first-val');
        Configure::write('nested.path', 'nested-val');
        $config = new ServiceConfig();

        $this->assertSame('first-val', $config->get('first'));
        $this->assertSame('nested-val', $config->get('nested.path'));
        $this->assertNull($config->get('nope'));
        $this->assertNull($config->get('nope'));
        $this->assertSame('default', $config->get('nested.nope', 'default'));
    }

    public function testHas()
    {
        Configure::write('first', 'first-val');
        Configure::write('nested.path', 'nested-val');
        Configure::write('nullval', null);
        $config = new ServiceConfig();

        $this->assertFalse($config->has('nope'));
        $this->assertTrue($config->has('first'));
        $this->assertTrue($config->has('nested.path'));
        $this->assertFalse($config->has('nullval'));
    }
}
