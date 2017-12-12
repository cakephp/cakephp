<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\Plugin;
use Cake\Core\PluginApp;
use Cake\TestSuite\TestCase;

/**
 * AppTest class
 */
class PluginAppTest extends TestCase
{

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
    }

    /**
     * testConfigForRoutesAndBootstrap
     *
     * @return void
     */
    public function testConfigForRoutesAndBootstrap()
    {
        $plugin = new PluginApp([
            'bootstrap' => false,
            'routes' => false
        ]);

        $this->assertFalse($plugin->isBootstrapEnabled());
        $this->assertFalse($plugin->isRouteLoadingEnabled());
    }
}
