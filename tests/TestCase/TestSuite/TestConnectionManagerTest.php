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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestConnectionManager;

class TestConnectionManagerTest extends TestCase
{
    public function testAliasConnections()
    {
        ConnectionManager::dropAlias('default');

        try {
            $exceptionThrown = false;
            ConnectionManager::get('default');
        } catch (MissingDatasourceConfigException $e) {
            $exceptionThrown = true;
        } finally {
            $this->assertTrue($exceptionThrown);
        }

        TestConnectionManager::$aliasConnectionIsLoaded = false;
        TestConnectionManager::aliasConnections();

        $this->assertSame(
            ConnectionManager::get('test'),
            ConnectionManager::get('default')
        );
    }
}
